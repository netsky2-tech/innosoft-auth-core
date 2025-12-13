<?php

use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;
use InnoSoft\AuthCore\Application\Listeners\LogSecurityEvents;

test('it logs user registration as a security event', function () {
    // 1. Arrange
    $logger = Mockery::mock(AuditLogger::class);
    $logger->shouldReceive('logSecurityEvent')
        ->once()
        ->with('user.registered', Mockery::on(function ($context) {
            return $context['email'] === 'audit@innosoft.com';
        }));

    $event = new UserRegistered('uuid-123', 'audit@innosoft.com');
    $listener = new LogSecurityEvents($logger);

    // 2. Act
    $listener->handleUserRegistered($event);

    // 3. Assert
    expect(true)->toBeTrue();
});