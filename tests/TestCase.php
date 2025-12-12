<?php

namespace InnoSoft\AuthCore\Tests;

use InnoSoft\AuthCore\AuthCoreServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // 1. load own migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 2. load sanctum migrations
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/laravel/sanctum/database/migrations');

        // 3. load Spatie Permission migrations
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/spatie/laravel-permission/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            AuthCoreServiceProvider::class,
            SanctumServiceProvider::class,
            PermissionServiceProvider::class,
        ];
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

        $app['config']->set('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
    }
}
