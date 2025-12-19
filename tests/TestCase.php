<?php

namespace InnoSoft\AuthCore\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use InnoSoft\AuthCore\AuthCoreServiceProvider;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar Factory para que busque en el namespace correcto del paquete
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'InnoSoft\\AuthCore\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            // own package
            AuthCoreServiceProvider::class,
            // dependencies package
            PermissionServiceProvider::class,
            ActivitylogServiceProvider::class,
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

        // 3. Load spatie activity log migrations
        if (!class_exists('CreateActivityLogTable')) {
            $stubPath = __DIR__.'/../vendor/spatie/laravel-activitylog/database/migrations/create_activity_log_table.php.stub';
            if (file_exists($stubPath)) {
                require_once $stubPath;
            }
        }
        if (class_exists('CreateActivityLogTable')) {
            (new \CreateActivityLogTable)->up();
        }

        $batchMigrationClass = 'AddBatchUuidColumnToActivityLogTable';
        if (!class_exists($batchMigrationClass)) {
            $batchStubPath = __DIR__.'/../vendor/spatie/laravel-activitylog/database/migrations/add_batch_uuid_column_to_activity_log_table.php.stub';
            if (file_exists($batchStubPath)) {
                require_once $batchStubPath;
            }
        }
        if (class_exists($batchMigrationClass)) {
            (new $batchMigrationClass)->up();
        }

        $eventMigrationClass = 'AddEventColumnToActivityLogTable';
        if (!class_exists($eventMigrationClass)) {
            $eventStubPath = __DIR__.'/../vendor/spatie/laravel-activitylog/database/migrations/add_event_column_to_activity_log_table.php.stub';
            if (file_exists($eventStubPath)) {
                require_once $eventStubPath;
            }
        }
        if (class_exists($eventMigrationClass)) {
            (new $eventMigrationClass)->up();
        }
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

        $app['config']->set('auth-core.user_model', User::class);
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

        // Configuración explícita para ActivityLog (Evita el error del CauserResolver)
        $app['config']->set('activitylog.default_auth_driver', 'sanctum');
        $app['config']->set('activitylog.database_connection', 'testing');
        $app['config']->set('activitylog.table_name', 'activity_log');

        $app['config']->set('app.key', 'base64:6Cu/ozj4w03HgX4pREMSTJrycv1qQxQzJw03HgX4pRE=');
    }
}
