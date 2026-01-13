<?php

namespace InnoSoft\AuthCore\Application\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use InnoSoft\AuthCore\Domain\Auth\Events\SecurityAlert;
use InnoSoft\AuthCore\Domain\Auth\Events\UserLoggedIn;
use InnoSoft\AuthCore\Domain\Permissions\Events\PermissionDeleted;
use InnoSoft\AuthCore\Domain\Permissions\Events\PermissionRegistered;
use InnoSoft\AuthCore\Domain\Permissions\Events\PermissionUpdated;
use InnoSoft\AuthCore\Domain\Roles\Events\RoleDeleted;
use InnoSoft\AuthCore\Domain\Roles\Events\RoleRegistered;
use InnoSoft\AuthCore\Domain\Roles\Events\RoleUpdated;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use InnoSoft\AuthCore\Domain\Users\Events\PasswordChanged;
use InnoSoft\AuthCore\Domain\Users\Events\PasswordResetCompleted;
use InnoSoft\AuthCore\Domain\Users\Events\RoleAssigned;
use InnoSoft\AuthCore\Domain\Users\Events\RoleRevoked;
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

    public function handleUserLoggedIn(UserLoggedIn $event): void
    {
        $this->logger->logSecurityEvent('auth.login.domain', [
            'user_id' => $event->userId(),
            'ip' => $event->ipAddress(),
            'user_agent' => $event->userAgent(),
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

    public function handleRoleAssigned(RoleAssigned $event): void
    {
        $this->logger->logSecurityEvent('user.role.assigned', [
            'user_id' => $event->userId(),
            'role_id' => $event->roleId(),
            'role_name' => $event->roleName(),
        ]);
    }

    public function handleRoleRevoked(RoleRevoked $event): void
    {
        $this->logger->logSecurityEvent('user.role.revoked', [
            'user_id' => $event->userId(),
            'role_id' => $event->roleId(),
            'role_name' => $event->roleName(),
        ]);
    }

    public function handleRoleRegistered(RoleRegistered $event): void
    {
        $this->logger->logSecurityEvent('role.registered', [
            'role_name' => $event->role(),
            'guard_name' => $event->guardName(),
        ]);
    }

    public function handleRoleUpdated(RoleUpdated $event): void
    {
        $this->logger->logSecurityEvent('role.updated', [
            'role_id' => $event->roleId(),
            'old_name' => $event->oldName(),
            'new_name' => $event->newName(),
        ]);
    }

    public function handleRoleDeleted(RoleDeleted $event): void
    {
        $this->logger->logSecurityEvent('role.deleted', [
            'role_name' => $event->roleName(),
            'guard_name' => $event->guardName(),
        ]);
    }

    public function handlePermissionRegistered(PermissionRegistered $event): void
    {
        $this->logger->logSecurityEvent('permission.registered', [
            'permission_name' => $event->permissionName(),
            'guard_name' => $event->guardName(),
        ]);
    }

    public function handlePermissionUpdated(PermissionUpdated $event): void
    {
        $this->logger->logSecurityEvent('permission.updated', [
            'permission_id' => $event->permissionId(),
            'old_name' => $event->oldName(),
            'new_name' => $event->newName(),
        ]);
    }

    public function handlePermissionDeleted(PermissionDeleted $event): void
    {
        $this->logger->logSecurityEvent('permission.deleted', [
            'permission_name' => $event->permissionName(),
            'guard_name' => $event->guardName(),
        ]);
    }

    public function handleSecurityAlert(SecurityAlert $event): void
    {
        $this->logger->logSecurityEvent('security.alert', [
            'threat_type' => $event->threatType(),
            'ip' => $event->ipAddress(),
            'user_id' => $event->userId(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            UserRegistered::class => 'handleUserRegistered',
            Login::class => 'handleLogin',
            UserLoggedIn::class => 'handleUserLoggedIn',
            Failed::class => 'handleLoginFailed',
            TwoFactorEnrollmentInitiated::class => 'handleTwoFactorInitiated',
            TwoFactorEnrollmentConfirmed::class => 'handleTwoFactorConfirmed',
            TwoFactorDisabled::class => 'handleTwoFactorDisabled',
            PasswordResetCompleted::class => 'handlePasswordReset',
            PasswordChanged::class => 'handlePasswordChanged',
            UserDeleted::class => 'handleUserDeleted',
            UserEmailChanged::class => 'handleEmailChanged',
            UserNameChanged::class => 'handleNameChanged',
            RoleAssigned::class => 'handleRoleAssigned',
            RoleRevoked::class => 'handleRoleRevoked',
            RoleRegistered::class => 'handleRoleRegistered',
            RoleUpdated::class => 'handleRoleUpdated',
            RoleDeleted::class => 'handleRoleDeleted',
            PermissionRegistered::class => 'handlePermissionRegistered',
            PermissionUpdated::class => 'handlePermissionUpdated',
            PermissionDeleted::class => 'handlePermissionDeleted',
            SecurityAlert::class => 'handleSecurityAlert',
        ];
    }
}
