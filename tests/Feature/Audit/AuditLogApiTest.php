<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crear permisos necesarios
    // El modelo User tiene $guard_name = 'api', por lo que los permisos deben crearse para ese guard
    Permission::create(['name' => 'audit.view', 'guard_name' => 'api']);
});

test('admin can view audit logs', function () {
    // 1. Arrange
    $admin = User::factory()->create();
    $admin->givePermissionTo('audit.view');

    // Limpiar logs previos creados por factories o eventos de creación de usuario
    Activity::truncate();

    // Crear algunos logs
    activity()->log('Test log 1');
    activity()->log('Test log 2');

    // 2. Act
    $response = $this->actingAs($admin)
        ->getJson(route('api.v1.audit.logs.index'));

    // 3. Assert
    $response->assertOk()
        // La estructura de respuesta envuelve los datos en 'data' -> 'data' debido a la paginación + ApiResponse trait
        // ApiResponse::successResponse recibe el resultado de $collection->response()->getData(true)
        // getData(true) devuelve un array con 'data', 'links', 'meta'.
        // successResponse lo mete dentro de 'data'.
        // Entonces la estructura final es:
        // {
        //   "success": true,
        //   "message": "...",
        //   "data": {
        //      "data": [...],
        //      "links": {...},
        //      "meta": {...}
        //   }
        // }
        ->assertJsonCount(2, 'data.data') 
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'description',
                        'created_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total'
                ]
            ]
        ]);
});

test('user without permission cannot view audit logs', function () {
    // 1. Arrange
    $user = User::factory()->create();
    // No permission given

    // 2. Act
    $response = $this->actingAs($user)
        ->getJson(route('api.v1.audit.logs.index'));

    // 3. Assert
    $response->assertForbidden();
});

test('can filter audit logs by event', function () {
    // 1. Arrange
    $admin = User::factory()->create();
    $admin->givePermissionTo('audit.view');

    // Limpiar logs previos
    Activity::truncate();

    activity()->event('login')->log('User logged in');
    activity()->event('logout')->log('User logged out');

    // 2. Act
    $response = $this->actingAs($admin)
        ->getJson(route('api.v1.audit.logs.index', ['event' => 'login']));

    // 3. Assert
    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonFragment(['description' => 'User logged in']);
});

test('can view user specific logs', function () {
    // 1. Arrange
    $admin = User::factory()->create();
    $admin->givePermissionTo('audit.view');

    $targetUser = User::factory()->create();
    
    // Limpiar logs previos
    Activity::truncate();

    activity()->causedBy($targetUser)->log('User action');
    activity()->causedBy($admin)->log('Admin action');

    // 2. Act
    $response = $this->actingAs($admin)
        ->getJson(route('api.v1.audit.users.logs', ['id' => $targetUser->id]));

    // 3. Assert
    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonFragment(['description' => 'User action']);
});
