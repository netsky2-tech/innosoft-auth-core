<?php
namespace InnoSoft\AuthCore;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InnoSoft\AuthCore\Application\Listeners\LogSecurityEvents;
use InnoSoft\AuthCore\Application\Listeners\SendEmailChangeAlerts;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use InnoSoft\AuthCore\Domain\Shared\HasDomainEvents;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Users\Events\UserEmailChanged;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Infrastructure\Auth\CacheTwoFactorChallengeService;
use InnoSoft\AuthCore\Infrastructure\Auth\GoogleTwoFactorProvider;
use InnoSoft\AuthCore\Infrastructure\Auth\LaravelPasswordTokenService;
use InnoSoft\AuthCore\Infrastructure\Auth\SanctumTokenIssuer;
use InnoSoft\AuthCore\Infrastructure\Bus\Event\LaravelEventBus;
use InnoSoft\AuthCore\Infrastructure\Persistence\EloquentUserRepository;
use InnoSoft\AuthCore\Infrastructure\Persistence\SpatieRoleRepository;
use InnoSoft\AuthCore\Infrastructure\Services\LaravelAuditLogger;
use InnoSoft\AuthCore\UI\Console\Commands\InstallAuthCoreCommand;
use InnoSoft\AuthCore\UI\Http\Middleware\CheckPermissionMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

class AuthCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // merge default settings
        $this->mergeConfigFrom(__DIR__.'/../config/auth-core.php', 'auth-core');

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

        $this->app->bind(DomainEventBus::class, LaravelEventBus::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(Router $router): void
    {
        // Gate global (super admin)
        $this->registerSuperAdminGate();

        // Automatic registry of middlewares
        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);

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
        Event::subscribe(LogSecurityEvents::class);

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
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}