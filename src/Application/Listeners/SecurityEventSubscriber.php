<?php

namespace InnoSoft\AuthCore\Application\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Users\Events\PasswordChanged;
use InnoSoft\AuthCore\Domain\Users\Events\PasswordResetCompleted;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorDisabled;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnrollmentConfirmed;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnrollmentInitiated;
use InnoSoft\AuthCore\Domain\Users\Events\UserDeleted;
use InnoSoft\AuthCore\Domain\Users\Events\UserEmailChanged;
use InnoSoft\AuthCore\Domain\Users\Events\UserNameChanged;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;

readonly class SecurityEventSubscriber implements ShouldQueue
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

    public function handleTwoFactorInitiated(TwoFactorEnrollmentInitiated $event): void
    {
        $this->logger->logSecurityEvent('auth.2fa.initiated', [
            'user_id' => $event->getUserId()
        ]);
    }

    public function handleTwoFactorConfirmed(TwoFactorEnrollmentConfirmed $event): void
    {
        $this->logger->logSecurityEvent('auth.2fa.confirmed', [
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

    public function handlePasswordChanged(PasswordChanged $event): void
    {
        $this->logger->logSecurityEvent('auth.password.changed', [
            'user_id' => $event->getUserId()
        ]);
    }

    public function handleUserDeleted(UserDeleted $event): void
    {
        $this->logger->logSecurityEvent('user.deleted', [
            'user_id' => $event->getUserId(),
            'email' => $event->getEmail()
        ]);
    }

    public function handleEmailChanged(UserEmailChanged $event): void
    {
        $this->logger->logSecurityEvent('user.email.changed', [
            'user_id' => $event->user(),
            'old_email' => $event->oldEmail(),
            'new_email' => $event->email()
        ]);
    }

    public function handleNameChanged(UserNameChanged $event): void
    {
        $this->logger->logSecurityEvent('user.name.changed', [
            'user_id' => $event->user(),
            'new_name' => $event->name()
        ]);
    }

    public function subscribe($events): array
    {
        return [
            UserRegistered::class => 'handleUserRegistered',
            Login::class => 'handleLogin',
            Failed::class => 'handleLoginFailed',
            TwoFactorEnrollmentInitiated::class => 'handleTwoFactorInitiated',
            TwoFactorEnrollmentConfirmed::class => 'handleTwoFactorConfirmed',
            TwoFactorDisabled::class => 'handleTwoFactorDisabled',
            PasswordResetCompleted::class => 'handlePasswordReset',
            PasswordChanged::class => 'handlePasswordChanged',
            UserDeleted::class => 'handleUserDeleted',
            UserEmailChanged::class => 'handleEmailChanged',
            UserNameChanged::class => 'handleNameChanged',
        ];
    }
}
