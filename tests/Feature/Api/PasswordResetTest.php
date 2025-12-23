<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('user can request password reset link', function () {
    User::create([
        'id' => 'uuid-reset',
        'name' => 'Reset User',
        'email' => 'reset@innosoft.com',
        'password' => 'hash'
    ]);

    $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reset@innosoft.com']);

    $response->assertOk()
        ->assertJson(['message' => 'If your email is registered, you will receive a reset link.']);
});

test('user can reset password with valid token', function () {
    // Arrange
    $user = User::create([
        'id' => 'uuid-reset-2',
        'name' => 'Reset User 2',
        'email' => 'reset2@innosoft.com',
        'password' => Hash::make('old-password')
    ]);

    // Generate token using the broker
    $token = Password::createToken($user);

    // Act
    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'reset2@innosoft.com',
        'token' => $token,
        'password' => 'NewSecurePass1!',
        'password_confirmation' => 'NewSecurePass1!'
    ]);

    // Assert
    $response->assertOk();

    $user->refresh();
    expect(Hash::check('NewSecurePass1!', $user->password))->toBeTrue();
});