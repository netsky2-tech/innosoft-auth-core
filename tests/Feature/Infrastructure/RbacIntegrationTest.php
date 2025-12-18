<?php

namespace InnoSoft\AuthCore\Tests\Feature\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use InnoSoft\AuthCore\Database\Seeders\AuthCoreSeeder;
use InnoSoft\AuthCore\Tests\TestCase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use PHPUnit\Framework\Attributes\Test;

class RbacIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Definimos rutas de prueba "al vuelo" para probar el middleware
        Route::middleware(['api', 'permission:test.secret'])->get('/test-protected', function () {
            return 'OK';
        });
    }

    #[Test]
    public function el_seeder_crea_roles_y_permisos_desde_config()
    {
        // Mock de la configuración
        Config::set('auth-core.roles_structure', [
            'TestRole' => ['test.permission_a', 'test.permission_b']
        ]);

        // Ejecutar Seeder
        $this->seed(AuthCoreSeeder::class);

        // Aserciones
        $this->assertDatabaseHas('roles', ['name' => 'TestRole', 'guard_name' => 'api']);
        $this->assertDatabaseHas('permissions', ['name' => 'test.permission_a', 'guard_name' => 'api']);
        $this->assertDatabaseHas('role_has_permissions', [
            'permission_id' => \Spatie\Permission\Models\Permission::where('name', 'test.permission_a')->first()->id,
            'role_id' => \Spatie\Permission\Models\Role::where('name', 'TestRole')->first()->id,
        ]);
    }

    #[Test]
    public function middleware_bloquea_usuario_sin_permiso()
    {
        // Usuario normal
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->get('/test-protected');

        $response->assertStatus(403);
    }

    #[Test]
    public function middleware_permite_usuario_con_permiso()
    {
        // Configurar entorno
        $this->seed(AuthCoreSeeder::class);

        // Crear usuario y dar permiso directo
        $user = User::factory()->create();
        $perm = \Spatie\Permission\Models\Permission::create(['name' => 'test.secret', 'guard_name' => 'api']);
        $user->givePermissionTo($perm);

        $response = $this->actingAs($user, 'api')
            ->get('/test-protected');

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    #[Test]
    public function super_admin_accede_a_todo_sin_permisos_explicitos()
    {
        // Configurar nombre del super admin
        Config::set('auth-core.super_admin_role', 'GodMode');
        $this->seed(AuthCoreSeeder::class);

        // Crear usuario y asignar Rol Super Admin (sin darle el permiso 'test.secret' explícitamente)
        $user = User::factory()->create();
        $user->assignRole('GodMode');

        $response = $this->actingAs($user, 'api')
            ->get('/test-protected');

        // Debería pasar gracias al Gate::before definido en el ServiceProvider
        $response->assertStatus(200);
    }
}