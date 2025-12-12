<?php
namespace InnoSoft\AuthCore;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Users\UserRepository;
use InnoSoft\AuthCore\Infrastructure\Auth\SanctumTokenIssuer;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
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
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
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