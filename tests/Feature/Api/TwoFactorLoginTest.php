<?php
test('user with 2fa enabled receives challenge instead of token', function () {
    // Arrange: User con 2FA secret
    $user = \InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User::factory()->create([
        'two_factor_secret' => 'TESTSECRET',
        'two_factor_confirmed_at' => now(),
    ]);

    // Act: Login normal
    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password'
    ]);

    // Assert: Recibimos estructura de desafío
    $response->assertJson([
        'requires_two_factor' => true
    ]);
    $this->assertArrayHasKey('challenge_token', $response->json());
});

test('user can complete login with valid otp', function () {
    // Arrange: User & Challenge token valid on cache
    $user = \InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User::factory()->create(['two_factor_secret' => 'TESTSECRET']);
    $token = 'valid-challenge-token';
    \Illuminate\Support\Facades\Cache::put($token, $user->id, 300);

    // Mock provider to accepts any number
    $this->mock(\InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorProvider::class)
        ->shouldReceive('verify')->with('TESTSECRET', '123456')->andReturn(true);

    // Act
    $response = $this->postJson('/api/auth/two-factor/verify', [
        'challenge_token' => $token,
        'code' => '123456',
        'device_name' => 'MyDevice'
    ]);

    // Assert: Token final
    $response->assertOk()->assertJsonStructure(['access_token']);
});