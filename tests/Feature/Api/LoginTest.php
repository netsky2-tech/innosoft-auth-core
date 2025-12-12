<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user can login and receive token', function () {
    // Arrange
    User::create([
        'id' => 'uuid-login',
        'name' => 'Login User',
        'email' => 'login@innosoft.com',
        'password' => Hash::make('password'),
    ]);

    // Act
    $response = $this->postJson('/api/auth/login', [
        'email' => 'login@innosoft.com',
        'password' => 'password',
        'device_name' => 'TestDevice'
    ]);

    // Assert
    $response->assertOk()
        ->assertJsonStructure(['access_token', 'token_type']);
});

test('login enforces rate limiting', function () {
    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'hacker@innosoft.com',
            'password' => 'wrong',
        ]);
    }

    $response->assertStatus(429);
});