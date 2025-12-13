<?php

namespace InnoSoft\AuthCore\Application\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;

class LogSecurityEvents
{
    public function __construct(
        private AuditLogger $logger
    ) {}

    public function handleUserRegistered(UserRegistered $event): void
    {
        $this->logger->logSecurityEvent('user.registered', [
            'user_id' => $event->user(),
            'email' => $event->email()
        ]);
    }

    public function handleLogin(Login $event): void
    {
        $this->logger->logSecurityEvent('auth.login.success', [
            'user_id' => $event->user->getAuthIdentifier(),
            'email' => $event->user->email ?? null,
        ]);
    }

    public function handleLoginFailed(Failed $event): void
    {
        $this->logger->logSecurityEvent('auth.login.failed', [
            'email' => $event->credentials['email'] ?? null,
        ]);
    }

    public function subscribe($events): array
    {
        return [
            UserRegistered::class => 'handleUserRegistered',
            Login::class => 'handleLogin',
            Failed::class => 'handleLoginFailed',
            // Add 2FA events here
        ];
    }
}