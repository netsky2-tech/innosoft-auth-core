<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// previous setup for define as private route
beforeEach(function () {
    Route::get('/test/protected', function () {
        return 'Protected Content';
    })->middleware(['api', 'auth:sanctum', 'permission:view_reports']);
});

test('user with permission can access protected route', function () {
    // 1. Arrange: Create user and permission
    Permission::create(['name' => 'view_reports', 'guard_name' => 'api']);

    $user = User::factory()->create();
    $user->givePermissionTo('view_reports');

    // 2. Act & Assert
    Sanctum::actingAs($user, ['*']);
    $this->getJson('/test/protected')
        ->assertOk()
        ->assertSee('Protected Content');
});

test('user without permission is denied access', function () {
    // 1. Arrange
    Permission::create(['name' => 'view_reports', 'guard_name' => 'api']);
    $user = User::factory()->create();

    // 2. Act & Assert
    Sanctum::actingAs($user, ['*']);
    $this->getJson('/test/protected')
        ->assertStatus(403)
        ->assertJson(['message' => 'Unauthorized. Missing permission: view_reports']);
});