<?php


use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User;

uses(RefreshDatabase::class);

test('disabling 2fa creates a security audit log', function () {
    // 1. Arrange
    $user = User::factory()->create([
        // ... tus datos ...
        'two_factor_secret' => 'SECRET',
        'two_factor_confirmed_at' => now(),
    ]);

    // mockeamos el servicio de dominio.
    $auditMock = Mockery::mock(\InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger::class);

    $auditMock->shouldReceive('logSecurityEvent')
        ->once()
        ->with(
            'auth.2fa.disabled',
            Mockery::on(function ($context) use ($user) {
                return $context['user_id'] === $user->id;
            })
        );

    // Intercambiamos la instancia en el contenedor de servicios
    $this->instance(\InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger::class, $auditMock);

    // 2. Act
    $this->actingAs($user)
        ->deleteJson('/api/v1/auth/two-factor/disable', [
            'current_password' => 'password'
        ])
        ->assertSuccessful();
});