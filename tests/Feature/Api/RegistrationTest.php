<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;

uses(RefreshDatabase::class, \InnoSoft\AuthCore\Tests\Traits\HasAuthHelpers::class);

test('api can register a new user', function () {
    $admin = $this->createSuperAdmin();

    $response = $this->actingAs($admin, 'api')
        ->postJson('/api/v1/auth/register', [
        'name' => 'API User',
        'email' => 'api@innosoft.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!'
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User registered successfully.'
        ]);

    expect(User::where('email', 'api@innosoft.com')->exists())->toBeTrue();
});

test('registration fails with invalid data', function () {

    $admin = $this->createSuperAdmin();

    $response = $this->actingAs($admin, 'api')
        ->postJson('/api/v1/auth/register', [
            'email' => 'not-an-email',
            'password' => 'short'
        ]);

    $response->assertStatus(422);
});