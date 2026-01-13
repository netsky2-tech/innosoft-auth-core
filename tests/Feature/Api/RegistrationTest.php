<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User;

uses(RefreshDatabase::class, \InnoSoft\AuthCore\Tests\Traits\HasAuthHelpers::class);

test('api can register a new user', function () {
    // Enable registration feature for this test
    Config::set('auth-core.features.registration', true);

    $response = $this->postJson('/api/v1/auth/register', [
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
    // Enable registration feature for this test
    Config::set('auth-core.features.registration', true);

    $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'not-an-email',
            'password' => 'short'
        ]);

    $response->assertStatus(422);
});

test('registration fails if feature is disabled', function () {
    // Disable registration feature for this test
    Config::set('auth-core.features.registration', false);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'API User',
        'email' => 'api@innosoft.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!'
    ]);

    $response->assertStatus(403);
});
