<?php

namespace InnoSoft\AuthCore\Tests;

use InnoSoft\AuthCore\AuthCoreServiceProvider;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            AuthCoreServiceProvider::class,
            SanctumServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // 1. load own migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 2. load sanctum migrations
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/laravel/sanctum/database/migrations');

        // 3. load Spatie Permission migrations
        $migration = include __DIR__ . '/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $migration->up();
    }
    protected function getEnvironmentSetUp($app): void
    {
        // env settings for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('auth.guards.api', [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ]);

        $app['config']->set('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);

        $app['config']->set('permission.cache.store', 'array');
        $app['config']->set('permission.cache.model_key', 'some-key');
        $app['config']->set('permission.cache.expiration_time', 0);
        $app['config']->set('permission.cache.enabled', false);
    }
}
