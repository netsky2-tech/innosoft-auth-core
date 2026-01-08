<?php
namespace InnoSoft\AuthCore;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use InnoSoft\AuthCore\Application\Auth\Commands\EnableTwoFactorCommand;
use InnoSoft\AuthCore\Application\Auth\Handlers\EnableTwoFactorHandler;
use InnoSoft\AuthCore\Application\Listeners\LogSecurityEvents;
use InnoSoft\AuthCore\Application\Listeners\SecurityEventSubscriber;
use InnoSoft\AuthCore\Application\Listeners\SendEmailChangeAlerts;
use InnoSoft\AuthCore\Domain\Auth\Services\DeviceSessionProvider;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Shared\HasDomainEvents;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator;
use InnoSoft\AuthCore\Domain\Users\Events\UserEmailChanged;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Infrastructure\Auth\CacheTwoFactorChallengeService;
use InnoSoft\AuthCore\Infrastructure\Auth\GoogleTwoFactorProvider;
use InnoSoft\AuthCore\Infrastructure\Auth\LaravelPasswordTokenService;
use InnoSoft\AuthCore\Infrastructure\Auth\SanctumDeviceSessionProvider;
use InnoSoft\AuthCore\Infrastructure\Auth\SanctumTokenIssuer;
use InnoSoft\AuthCore\Infrastructure\Bus\Event\LaravelEventBus;
use InnoSoft\AuthCore\Infrastructure\Persistence\EloquentUserRepository;
use InnoSoft\AuthCore\Infrastructure\Persistence\SpatieRoleRepository;
use InnoSoft\AuthCore\Infrastructure\Services\LaravelAuditLogger;
use InnoSoft\AuthCore\Infrastructure\Teams\HostTeamMembershipValidator;
use InnoSoft\AuthCore\UI\Console\Commands\InstallAuthCoreCommand;
use InnoSoft\AuthCore\UI\Http\Middleware\CheckPermissionMiddleware;
use InnoSoft\AuthCore\UI\Http\Middleware\TeamContextMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\Finder\Finder;

class AuthCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // merge default settings
        $this->mergeConfigFrom(__DIR__.'/../config/auth-core.php', 'auth-core');

        // Si la feature de Teams está activa, forzamos la configuración de Spatie
        if (config('auth-core.features.teams', false)) {
            config(['permission.teams' => true]);
            
            // Verificación de seguridad para el desarrollador
            // Si activan teams pero no bindean el repositorio, fallará el Middleware.
            // No lanzamos excepción aquí para permitir que el Host haga el bind en su propio Provider,
            // pero es importante documentarlo.
        }

        // Biding del repositorio con modelo dinamico
        $this->app->bind(UserRepository::class, function ($app) {
            $modelClass = config('auth-core.user_model');

            if (!class_exists($modelClass)) {
                throw new \RuntimeException("The configured user model [$modelClass] does not exist.");
            }

            return new EloquentUserRepository(new $modelClass);
        });

        // Biding interfaces and implementations
        $this->app->bind(TokenIssuer::class, SanctumTokenIssuer::class);
        $this->app->bind(PasswordTokenService::class, LaravelPasswordTokenService::class);
        $this->app->bind(TwoFactorProvider::class, GoogleTwoFactorProvider::class);
        $this->app->bind(TwoFactorChallengeService::class, CacheTwoFactorChallengeService::class);
        $this->app->bind(AuditLogger::class, LaravelAuditLogger::class);
        $this->app->bind(RoleRepository::class, SpatieRoleRepository::class);
        $this->app->bind(DeviceSessionProvider::class, SanctumDeviceSessionProvider::class);
        $this->app->bind(TeamMembershipValidator::class, HostTeamMembershipValidator::class);

        $this->app->bind(DomainEventBus::class, LaravelEventBus::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(Router $router): void
    {
        // biding commands / handlers
        $this->registerCommands();

        // Gate global (super admin)
        $this->registerSuperAdminGate();

        // Automatic registry of middlewares
        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);
        $router->aliasMiddleware('auth.context', TeamContextMiddleware::class);

        // Publish settings
        $this->publishes([
            __DIR__.'/../config/auth-core.php' => config_path('auth-core.php'),
        ], 'innosoft-auth-config');

        // Publish migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load API routes
        $this->loadRoutesFrom(__DIR__.'/UI/Routes/api.php');

        // Registrar alias de middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission', CheckPermissionMiddleware::class);

        $this->configureRateLimiting();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAuthCoreCommand::class,
            ]);
        }

        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->mixedCase()->numbers()->symbols()->uncompromised()
                : $rule;
        });

        // ============================================================
        // 🚀 REGISTRO DE EVENTOS DEL PAQUETE
        // ============================================================

        // Sync Subscriber
        Event::subscribe(SecurityEventSubscriber::class);

        // Async Listener
        /*Event::listen(
            UserEmailChanged::class,
            SendEmailChangeAlerts::class
        );*/

    }

    protected function registerSuperAdminGate(): void
    {
        Gate::before(function ($user, $ability) {
            // Usar config estricto
            $roleName = config('auth-core.super_admin_role', 'SuperAdmin');

            // Verificamos si el método existe para evitar errores si el User model es ajeno
            if (method_exists($user, 'hasRole')) {
                return $user->hasRole($roleName) ? true : null;
            }
            return null;
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth-core.login', function (Request $request) {
            // Use email if available, otherwise IP. This prevents IP blocking for multiple users (NAT)
            // but still protects against brute force on a specific account.
            $key = $request->input('email') ?: $request->ip();
            return Limit::perMinute(5)->by($key);
        });
    }

    protected function registerCommands(): void
    {
        $map = [];
        $basePath = __DIR__ . '/Application';
        $baseNamespace = 'InnoSoft\\AuthCore\\Application\\';

        // 1. Definimos los módulos que queremos escanear
        $modules = ['Auth', 'Roles', 'Users', 'Teams'];

        foreach ($modules as $module) {
            // Rutas a escanear
            $commandPath = "$basePath/$module/Commands";
            $queryPath   = "$basePath/$module/Queries";

            // A. Escanear Comandos
            if (is_dir($commandPath)) {
                $map = array_merge($map, $this->discoverHandlers(
                    path: $commandPath,
                    namespace: "{$baseNamespace}{$module}\\Commands",
                    handlerNamespace: "{$baseNamespace}{$module}\\Handlers",
                    type: 'Command'
                ));
            }

            // B. Escanear Queries (Opcional, si usas Bus para queries también)
            if (is_dir($queryPath)) {
                // Nota: Ajusta 'Handlers' si tus QueryHandlers están en Application/Module/Queries/Handlers
                // Basado en tu árbol, parece que están en Users/Queries/Handlers
                $map = array_merge($map, $this->discoverHandlers(
                    path: $queryPath,
                    namespace: "{$baseNamespace}{$module}\\Queries",
                    handlerNamespace: "{$baseNamespace}{$module}\\Queries\\Handlers",
                    type: 'Query'
                ));
            }
        }

        // Registrar todo el mapa de una sola vez
        // Bus::map($map); // <-- COMENTADO: El mapeo manual puede causar conflictos si el Dispatcher ya intenta resolver automáticamente.
        // Sin embargo, si usamos Bus::map, estamos forzando el mapeo.
        // El problema es que el mapeo se hizo cuando el handler tenía __invoke, y ahora tiene handle.
        // Laravel Bus debería ser capaz de encontrar 'handle' automáticamente si mapeamos la clase.
        
        // El error "Call to undefined method ... ListUsersQuery::__invoke()" sugiere que ALGUIEN está intentando invocar el QUERY OBJECT como si fuera un callable,
        // O que el handler mapeado se está intentando invocar y Laravel asume __invoke si no encuentra handle? No.
        //
        // Espera, el error dice: Call to undefined method ... ListUsersQuery::__invoke()
        // ¡Dice ListUsersQuery! NO ListUsersQueryHandler.
        // Esto significa que se está intentando ejecutar el Query Object mismo como si fuera el handler.
        // Esto pasa cuando el Bus no encuentra el handler mapeado y trata de ejecutar el comando/query mismo (self-handling).
        
        // ¿Por qué no encuentra el handler?
        // Porque cambié __invoke a handle, pero tal vez el mapeo en memoria sigue apuntando a algo raro o el mapeo falló silenciosamente.
        // O, más probable:
        // En discoverHandlers:
        // $handlerName = Str::replaceLast($type, 'Handler', $className);
        // ListUsersQuery -> ListUsersHandler (NO ListUsersQueryHandler)
        //
        // Mi archivo se llama ListUsersQueryHandler.php
        // Pero mi lógica de descubrimiento hace:
        // $className = ListUsersQuery
        // $type = Query
        // replaceLast('Query', 'Handler', 'ListUsersQuery') -> ListUsersHandler
        //
        // ¡Ahí está el error!
        // Si el archivo del Query es "ListUsersQuery.php", y el Handler es "ListUsersQueryHandler.php".
        // Mi lógica busca "ListUsersHandler".
        //
        // Si el archivo del Query fuera "ListUsers.php" (sin sufijo Query), entonces replaceLast no aplicaría si busco 'Query'.
        //
        // Revisemos la lógica en discoverHandlers:
        // $className = $file->getBasename('.php'); // Ej: ListUsersQuery
        // $handlerName = Str::replaceLast($type, 'Handler', $className);
        //
        // Si type='Query':
        // ListUsersQuery -> ListUsersHandler.
        //
        // Pero mi clase real es ListUsersQueryHandler.
        // Entonces el mapa queda: ListUsersQuery => ListUsersHandler.
        // Y como ListUsersHandler NO existe, el mapa no se crea (por el check class_exists).
        //
        // if (class_exists($commandClass) && class_exists($handlerClass)) { ... }
        //
        // Entonces, ListUsersQuery NO se mapea.
        // Al no estar mapeado, el Bus intenta ejecutar el Query como self-handling, buscando un método handle() o __invoke() en el Query mismo.
        // Y falla.
        
        // Solución: Ajustar la convención de nombres en discoverHandlers.
        // Si el handler se llama ListUsersQueryHandler (mantiene el sufijo Query), entonces la lógica debe ser diferente.
        //
        // Convención actual en el proyecto:
        // Command: CreateUserCommand -> Handler: CreateUserHandler (Sufijo Command reemplazado por Handler)
        // Query: ListUsersQuery -> Handler: ListUsersQueryHandler (Sufijo Query CONSERVADO + Handler)
        //
        // ¡Ajá! Inconsistencia en la convención de nombres entre Comandos y Queries.
        //
        // Comandos: CreateUserCommand -> CreateUserHandler (Reemplaza)
        // Queries: ListUsersQuery -> ListUsersQueryHandler (Agrega)
        
        // Voy a modificar discoverHandlers para soportar ambas convenciones o ajustar la lógica para Queries.
        
        Bus::map($map);
    }

    /**
     * Helper para encontrar y emparejar Clases con sus Handlers
     */
    private function discoverHandlers(string $path, string $namespace, string $handlerNamespace, string $type): array
    {
        $map = [];
        $files = (new Finder())->in($path)->files()->name('*.php');

        foreach ($files as $file) {
            $className = $file->getBasename('.php');
            $commandClass = "$namespace\\$className";

            // Estrategia 1: Reemplazo de sufijo (Estilo Command)
            // CreateUserCommand -> CreateUserHandler
            $handlerNameReplace = Str::replaceLast($type, 'Handler', $className);
            $handlerClassReplace = "$handlerNamespace\\$handlerNameReplace";

            // Estrategia 2: Adición de sufijo (Estilo Query explícito)
            // ListUsersQuery -> ListUsersQueryHandler
            $handlerNameAppend = $className . 'Handler';
            $handlerClassAppend = "$handlerNamespace\\$handlerNameAppend";

            if (class_exists($commandClass)) {
                if (class_exists($handlerClassReplace)) {
                    $map[$commandClass] = $handlerClassReplace;
                } elseif (class_exists($handlerClassAppend)) {
                    $map[$commandClass] = $handlerClassAppend;
                }
            }
        }

        return $map;
    }
}