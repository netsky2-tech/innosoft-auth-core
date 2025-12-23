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
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@innosoft.com',
        'password' => 'password',
        'device_name' => 'TestDevice'
    ]);

    // Assert
    $response->assertOk();
    expect($response->json('data.access_token'))->not->toBeEmpty()
        ->and($response->json('success'))->toBeTrue();
});

test('login enforces rate limiting', function () {
    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'hacker@innosoft.com',
            'password' => 'wrong',
        ]);
    }

    $response->assertStatus(429);
});