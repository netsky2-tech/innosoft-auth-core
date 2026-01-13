<?php

namespace InnoSoft\AuthCore\Domain\Users\Aggregates;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\HasDomainEvents;
use InnoSoft\AuthCore\Domain\Users\Events\PasswordChanged;
use InnoSoft\AuthCore\Domain\Users\Events\RoleAssigned;
use InnoSoft\AuthCore\Domain\Users\Events\RoleRevoked;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorDisabled;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnrollmentConfirmed;
use InnoSoft\AuthCore\Domain\Users\Events\TwoFactorEnrollmentInitiated;
use InnoSoft\AuthCore\Domain\Users\Events\UserDeleted;
use InnoSoft\AuthCore\Domain\Users\Events\UserEmailChanged;
use InnoSoft\AuthCore\Domain\Users\Events\UserNameChanged;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;
class User
{
    use HasDomainEvents;

    public function __construct(
        private readonly string             $id,
        private string                      $name,
        private EmailAddress                $email,
        private string                      $passwordHash,
        private ?string                     $twoFactorSecret,
        private ?bool                       $twoFactorConfirmed,
        private ?array                      $twoFactorRecoveryCodes,
        private ?DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $deletedAt = null,
    ){}


    public static function register(
        string $id,
        string $name,
        EmailAddress $email,
        string $passwordHash,
        ?string $twoFactorSecret = null,
        ?bool $twoFactorConfirmed = false,
        ?string $twoFactorRecoveryCodes = null,
        ?DateTimeImmutable $createdAt = null
    ): self {
        $user = new self($id, $name, $email, $passwordHash, $twoFactorSecret, $twoFactorConfirmed, $twoFactorRecoveryCodes, $createdAt, null);

        // register domain event
        $user->record(new UserRegistered($id, $email->getValue()));

        return $user;
    }

    /**
     * Reconstitute user from persistence (Hydration).
     * Used by Repositories mapping Eloquent -> Domain.
     */
    public static function fromPersistence(
        string $id,
        string $name,
        string $email,
        string $passwordHash,
        ?string $twoFactorSecret,
        bool $twoFactorConfirmed,
        ?string $twoFactorRecoveryCodes,
        ?DateTimeImmutable $createdAt,
        ?DateTimeImmutable $deletedAt = null
    ): self {
        // create instance without dispatch event
        return new self(
            $id,
            $name,
            new EmailAddress($email),
            $passwordHash,
            $twoFactorSecret,
            $twoFactorConfirmed,
            $twoFactorRecoveryCodes,
            $createdAt,
            $deletedAt
        );
    }

    public function updatePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;

        $this->record(new PasswordChanged($this->id));
    }
    public function updateName(string $name): void
    {
        if ($this->name === $name) {
            return;
        }

        $this->name = $name;
        $this->record(new UserNameChanged($this->id, $this->name));
    }

    public function updateEmail(EmailAddress $newEmail): void
    {
        $oldEmail = $this->email;

        $this->email = $newEmail;
        $this->record(new UserEmailChanged($this->id, $newEmail->getValue(), $oldEmail->getValue()));
    }

    public function initiateTwoFActorEnrollment(string $encryptedSecret): void
    {
        $this->twoFactorSecret = $encryptedSecret;
        $this->twoFactorConfirmed = false;

        $this->record(new TwoFactorEnrollmentInitiated($this->id));
    }
    public function disableTwoFactor(): void
    {
        $this->twoFactorSecret = null;
        $this->twoFactorConfirmed = false;
        $this->twoFactorRecoveryCodes = null;

        $this->record(new TwoFactorDisabled($this->id));
    }

    /**
     * Completes the 2FA enrollment process.
     */
    public function completeTwoFactorEnrollment(array $recoveryCodes): void
    {
        $this->twoFactorConfirmed = true;
        $this->twoFactorRecoveryCodes = $recoveryCodes;

        $this->record(new TwoFactorEnrollmentConfirmed(
            $this->id,
        ));

    }
    public function confirmTwoFactor(): void
    {
        $this->twoFactorConfirmed = true;
    }

    public function setRecoveryCodes(array $recoveryCodes): void
    {
        $this->twoFactorRecoveryCodes = $recoveryCodes;
    }

    public function delete(): void
    {
        // if ($this->hasDebt()) { throw new CannotDeleteUserWithDebtException(); }

        $this->deletedAt = new DateTimeImmutable();

        $this->record(new UserDeleted(
            $this->id,
            $this->email->getValue()
        ));
    }

    // This method is called when a role is assigned to the user
    // Since roles are managed via Eloquent/Spatie, this is a bridge to emit the domain event
    public function notifyRoleAssigned(string $roleId, string $roleName): void
    {
        $this->record(new RoleAssigned(
            $this->id,
            $roleId,
            $roleName
        ));
    }

    public function notifyRoleRevoked(string $roleId, string $roleName): void
    {
        $this->record(new RoleRevoked(
            $this->id,
            $roleId,
            $roleName
        ));
    }

    public function getTwoFactorRecoveryCodes(): ?array
    {
        return $this->twoFactorRecoveryCodes;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return !empty($this->twoFactorSecret) && $this->twoFactorConfirmed;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): EmailAddress
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function getTwoFactorConfirmed(): ?bool
    {
        return $this->twoFactorConfirmed;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
