<?php

namespace InnoSoft\AuthCore\Application\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Users\Events\PasswordResetCompleted;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorDisabled;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnabled;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;

readonly class LogSecurityEvents
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
            'email' => $event->user->getAuthIdentifierName() ?? null,
        ]);
    }

    public function handleLoginFailed(Failed $event): void
    {
        $this->logger->logSecurityEvent('auth.login.failed', [
            'email' => $event->credentials->email ?? null,
        ]);
    }
    public function handleTwoFactorEnabled(TwoFactorEnabled $event): void
    {
        $this->logger->logSecurityEvent('auth.2fa.enabled', [
            'user_id' => $event->getUserId()
        ]);
    }

    public function handleTwoFactorDisabled(TwoFactorDisabled $event): void
    {
        $this->logger->logSecurityEvent('auth.2fa.disabled', [
            'user_id' => $event->getUserId()
        ]);
    }

    public function handlePasswordReset(PasswordResetCompleted $event): void
    {
        $this->logger->logSecurityEvent('auth.password.reset', [
            'user_id' => $event->getUserId(),
            'email'   => $event->getEmail()
        ]);
    }

    public function subscribe($events): array
    {
        return [
            UserRegistered::class => 'handleUserRegistered',
            Login::class => 'handleLogin',
            Failed::class => 'handleLoginFailed',
            TwoFactorEnabled::class => 'handleTwoFactorEnabled',
            TwoFactorDisabled::class => 'handleTwoFactorDisabled',
            PasswordResetCompleted::class => 'handlePasswordReset',
        ];
    }
}