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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use InnoSoft\AuthCore\Application\Listeners\SecurityEventSubscriber;
use InnoSoft\AuthCore\Domain\Auth\Services\DeviceSessionProvider;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Permissions\PermissionRepository;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Infrastructure\Auth\CacheTwoFactorChallengeService;
use InnoSoft\AuthCore\Infrastructure\Auth\GoogleTwoFactorProvider;
use InnoSoft\AuthCore\Infrastructure\Auth\LaravelPasswordTokenService;
use InnoSoft\AuthCore\Infrastructure\Auth\SanctumDeviceSessionProvider;
use InnoSoft\AuthCore\Infrastructure\Auth\SanctumTokenIssuer;
use InnoSoft\AuthCore\Infrastructure\Bus\Event\LaravelEventBus;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Repositories\SpatiePermissionRepository;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Repositories\SpatieRoleRepository;
use InnoSoft\AuthCore\Infrastructure\Services\LaravelAuditLogger;
use InnoSoft\AuthCore\Infrastructure\Teams\HostTeamMembershipValidator;
use InnoSoft\AuthCore\UI\Console\Commands\InstallAuthCoreCommand;
use InnoSoft\AuthCore\UI\Console\Commands\PruneAuthDataCommand;
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
        $this->app->bind(PermissionRepository::class, SpatiePermissionRepository::class);
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

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'auth-core');

        // Publish translations
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/auth-core'),
        ], 'auth-core-translations');

        // Set locale if configured
        if ($locale = config('auth-core.locale')) {
            app()->setLocale($locale);
        }

        // Load API routes
        $this->registerRoutes();

        // Registrar alias de middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission', CheckPermissionMiddleware::class);

        $this->configureRateLimiting();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallAuthCoreCommand::class,
                PruneAuthDataCommand::class,
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

    protected function registerRoutes(): void
    {
        // V1 Routes
        Route::prefix(config('auth-core.prefix', 'api') . '/v1')
            ->group(__DIR__.'/UI/Routes/v1.php');
            
        // Future V2 Routes
        // Route::prefix(config('auth-core.prefix', 'api') . '/v2')
        //     ->group(__DIR__.'/UI/Routes/v2.php');
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
            $limit = config('auth-core.rate_limits.login', 5);
            return Limit::perMinute($limit)->by($key);
        });

        RateLimiter::for('auth-core.api', function (Request $request) {
            $limit = config('auth-core.rate_limits.api', 60);
            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function registerCommands(): void
    {
        $map = [];
        $basePath = __DIR__ . '/Application';
        $baseNamespace = 'InnoSoft\\AuthCore\\Application\\';

        // 1. Definimos los módulos que queremos escanear
        $modules = ['Auth', 'Roles', 'Users', 'Teams', 'Audit', 'Permissions'];

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