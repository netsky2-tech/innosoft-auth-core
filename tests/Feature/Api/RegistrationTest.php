<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;

uses(RefreshDatabase::class);

test('api can register a new user', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'API User',
        'email' => 'api@innosoft.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!'
    ]);

    $response->assertStatus(201)
    ->assertJson(['message' => 'User registered successfully']);

    expect(User::where('email', 'api@innosoft.com')->exists())->toBeTrue();
});

test('registration fails with invalid data', function () {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'not-an-email',
        'password' => 'short'
    ]);

    $response->assertStatus(422);
});