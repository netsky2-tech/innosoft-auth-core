<?php

namespace InnoSoft\AuthCore\UI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
class InstallAuthCoreCommand extends Command
{
    protected $signature = 'auth-core:install';
    protected $description = 'Install InnoSoft Auth Core package resources and dependencies';

    public function handle(): int
    {
        $this->info('🚀 Starting InnoSoft Auth Core installation...');

        // 1. Publicar Configuración
        $this->publishConfig();

        // 2. Publicar Migraciones (Smart Check)
        $this->publishMigrations();

        // 3. Validar que TODO esté correcto antes de continuar
        if (!$this->validateMigrationsIntegrity()) {
            $this->error('❌ Critical migrations are missing. Please check the logs above.');
            return self::FAILURE;
        }

        // 4. Preguntar por ejecución de migraciones
        if ($this->confirm('Do you want to run the migrations now?', true)) {
            $this->call('migrate');
            $this->info('✅ Migrations executed.');
        } else {
            $this->warn('⚠️  Please run [php artisan migrate] manually later.');
        }

        // 5. Preguntar por Seeding
        if ($this->confirm('Do you want to seed the default Roles & Permissions structure?', true)) {
            $this->call('db:seed', [
                '--class' => "InnoSoft\AuthCore\Database\Seeders\AuthCoreSeeder"
            ]);
            $this->info('✅ Database seeded successfully.');
        }

        $this->newLine();
        $this->info('🎉 InnoSoft Auth Core installed successfully!');

        return self::SUCCESS;
    }

    /**
     * Publica la configuración si no existe.
     */
    protected function publishConfig(): void
    {
        if (File::exists(config_path('auth-core.php'))) {
            $this->line('   - Config file already exists. Skipping...');
        } else {
            $this->info('📦 Publishing configuration...');
            $this->callSilent('vendor:publish', [
                '--provider' => "InnoSoft\AuthCore\AuthCoreServiceProvider",
                '--tag'      => "innosoft-auth-config"
            ]);
        }
    }

    /**
     * Publica las migraciones de dependencias si no existen en disco.
     */
    protected function publishMigrations(): void
    {
        $this->info('📦 Checking migrations...');

        // A. Spatie Permission
        if ($this->migrationFileExists('create_permission_tables')) {
            $this->line('   - Permission tables migration already exists.');
        } else {
            $this->info('   + Publishing Spatie Permission migrations...');
            $this->callSilent('vendor:publish', [
                '--provider' => "Spatie\Permission\PermissionServiceProvider",
                '--tag'      => "permission-migrations"
            ]);
        }

        // B. Spatie Activity Log
        $missingActivityLogMigrations =
            !$this->migrationFileExists('create_activity_log_table') ||
            !$this->migrationFileExists('add_event_column_to_activity_log_table') ||
            !$this->migrationFileExists('add_batch_uuid_column_to_activity_log_table');

        if ($missingActivityLogMigrations) {
            $this->info('   + Publishing Spatie Activity Log migrations...');
            $this->callSilent('vendor:publish', [
                '--provider' => "Spatie\Activitylog\ActivitylogServiceProvider",
                '--tag'      => "activitylog-migrations"
            ]);
        } else {
            $this->line('   - Activity Log base migration already exists.');
        }
    }

    /**
     * Valida que las migraciones críticas (incluyendo actualizaciones de columnas) existan.
     */
    protected function validateMigrationsIntegrity(): bool
    {
        $missing = [];

        // 1. Verificar tabla de permisos
        if (!$this->migrationFileExists('create_permission_tables')) {
            $missing[] = 'create_permission_tables';
        }

        // 2. Verificar tabla de logs base
        if (!$this->migrationFileExists('create_activity_log_table')) {
            $missing[] = 'create_activity_log_table';
        }

        // 3. Verificar actualizaciones críticas de Activity Log (batch_uuid y event)

        if (!$this->migrationFileExists('add_event_column_to_activity_log_table')) {
            $this->warn('   ⚠️ Missing migration: add_event_column_to_activity_log_table');
            $missing[] = 'add_event_column_to_activity_log_table';
        }

        if (!$this->migrationFileExists('add_batch_uuid_column_to_activity_log_table')) {
            $this->warn('   ⚠️ Missing migration: add_batch_uuid_column_to_activity_log_table');
            $missing[] = 'add_batch_uuid_column_to_activity_log_table';
        }

        if (count($missing) > 0) {
            $this->error('The following migrations were not found in database/migrations:');
            foreach ($missing as $m) {
                $this->line(" - $m");
            }
            $this->line('Try running: php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"');
            return false;
        }

        return true;
    }

    /**
     * Helper para verificar si existe un archivo de migración ignorando el timestamp.
     * Ejemplo: busca "*_create_permission_tables.php"
     */
    protected function migrationFileExists(string $filenamePart): bool
    {
        $files = glob(database_path('migrations/*' . $filenamePart . '.php'));
        return count($files) > 0;
    }
}