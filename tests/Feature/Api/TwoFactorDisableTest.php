<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user can disable two factor authentication with valid password', function () {
    // 1. Arrange: User with 2FA active
    $user = User::factory()->create([
        'password' => Hash::make('MySecurePassword!'),
        'two_factor_secret' => 'SECRET',
        'two_factor_confirmed_at' => now(),
    ]);

    // 2. Act
    $response = $this->actingAs($user)
        ->deleteJson('/api/auth/two-factor', [
            'current_password' => 'MySecurePassword!'
        ]);

    // 3. Assert
    $response->assertOk()
        ->assertJson(['message' => 'Two factor authentication disabled successfully.']);

    // verify bd fields
    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull();
});

test('user cannot disable two factor with incorrect password', function () {
    // 1. Arrange
    $user = User::factory()->create([
        'password' => Hash::make('MySecurePassword!'),
        'two_factor_secret' => 'SECRET',
    ]);

    // 2. Act
    $response = $this->actingAs($user)
        ->deleteJson('/api/auth/two-factor', [
            'current_password' => 'WrongPassword'
        ]);

    // 3. Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);

    $user->refresh();
    expect($user->two_factor_secret)->toBe('SECRET');
});