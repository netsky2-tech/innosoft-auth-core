<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login action is audited', function () {
    // 1. Arrange
    $loggerMock = Mockery::mock(AuditLogger::class);

    $this->app->instance(AuditLogger::class, $loggerMock);

    $user = User::factory()->create([
        'email' => 'audit_me@innosoft.com',
        'password' => Hash::make('password')
    ]);

    // 2. Act
    $this->postJson('/api/v1/auth/login', [
        'email' => 'audit_me@innosoft.com',
        'password' => 'password'
    ]);

    // 3. Assert
    $loggerMock->shouldHaveReceived('logSecurityEvent')
        ->once()
        ->with('auth.login.success', Mockery::on(function ($context) use ($user) {
            return $context['user_id'] == $user->id
                && isset($context['email']);
        }));
});