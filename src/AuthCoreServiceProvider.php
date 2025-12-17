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
use InnoSoft\AuthCore\Application\Listeners\LogSecurityEvents;
use InnoSoft\AuthCore\Domain\Auth\Services\PasswordTokenService;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider;
use InnoSoft\AuthCore\Domain\Roles\RoleRepository;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use InnoSoft\AuthCore\Infrastructure\Auth\CacheTwoFactorChallengeService;
use InnoSoft\AuthCore\Infrastructure\Auth\GoogleTwoFactorProvider;
use InnoSoft\AuthCore\Infrastructure\Auth\LaravelPasswordTokenService;
use InnoSoft\AuthCore\Infrastructure\Auth\SanctumTokenIssuer;
use InnoSoft\AuthCore\Infrastructure\Persistence\EloquentUserRepository;
use InnoSoft\AuthCore\Infrastructure\Persistence\SpatieRoleRepository;
use InnoSoft\AuthCore\Infrastructure\Services\LaravelAuditLogger;
use InnoSoft\AuthCore\UI\Http\Middleware\CheckPermissionMiddleware;

class AuthCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // merge default settings
        $this->mergeConfigFrom(__DIR__.'/../config/auth-core.php', 'auth-core');

        // Biding interfaces and implementations
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(TokenIssuer::class, SanctumTokenIssuer::class);
        $this->app->bind(PasswordTokenService::class, LaravelPasswordTokenService::class);
        $this->app->bind(TwoFactorProvider::class, GoogleTwoFactorProvider::class);
        $this->app->bind(TwoFactorChallengeService::class, CacheTwoFactorChallengeService::class);
        $this->app->bind(AuditLogger::class, LaravelAuditLogger::class);
        $this->app->bind(RoleRepository::class, SpatieRoleRepository::class);

        Event::subscribe(LogSecurityEvents::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(Router $router): void
    {
        // Gate global (super admin)
        Gate::before(function ($user, $ability) {
            $superAdminRole = config('innosoft-auth.super_admin_role', 'SuperAdmin');
            return $user->hasRole($superAdminRole) ? true : null;
        });

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
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth-core.login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}