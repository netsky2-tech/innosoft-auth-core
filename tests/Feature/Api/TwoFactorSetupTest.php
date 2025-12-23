<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;

uses(RefreshDatabase::class);

test('authenticated user can initiate 2fa setup', function () {
    // Arrange
    $user = User::factory()->create();

    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/v1/auth/two-factor/enable');

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'secret',
                'qr_code_url'
            ]
        ]);

    // Verify that the secret key was saved but not confirmed
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

test('user can confirm 2fa setup with valid code', function () {
    // Arrange
    $user = User::factory()->create([
        'two_factor_secret' => 'TESTSECRET',
        'two_factor_confirmed_at' => null
    ]);

    $this->mock(\InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider::class)
        ->shouldReceive('verify')->with('TESTSECRET', '123456')->andReturn(true)
        ->shouldReceive('generateRecoveryCodes')->andReturn(['code-1', 'code-2']);

    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/v1/auth/two-factor/confirm', [
            'code' => '123456'
        ]);

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'recovery_codes',
            ]
        ]);

    $user->refresh();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
});