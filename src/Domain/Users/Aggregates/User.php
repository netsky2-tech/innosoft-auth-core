<?php

namespace InnoSoft\AuthCore\Domain\Users\Aggregates;

use InnoSoft\AuthCore\Domain\Shared\HasDomainEvents;
use InnoSoft\AuthCore\Domain\Users\ValueObjects\EmailAddress;
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;
class User
{
    use HasDomainEvents;

    public function __construct(
        private readonly string       $id,
        private readonly string       $name,
        private readonly EmailAddress $email,
        private string $passwordHash,
        private ?string $twoFactorSecret,
        private ?bool $twoFactorConfirmed,
        private ?string $twoFactorRecoveryCodes
    ){}

    public static function register(
        string $id,
        string $name,
        EmailAddress $email,
        string $passwordHash,
        ?string $twoFactorSecret = null,
        ?bool $twoFactorConfirmed = false,
        ?string $twoFactorRecoveryCodes = null
    ): self {
        $user = new self($id, $name, $email, $passwordHash, $twoFactorSecret, $twoFactorConfirmed, $twoFactorRecoveryCodes);

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
        ?string $twoFactorRecoveryCodes
    ): self {
        // create instance without dispatch event
        return new self(
            $id,
            $name,
            new EmailAddress($email),
            $passwordHash,
            $twoFactorSecret,
            $twoFactorConfirmed,
            $twoFactorRecoveryCodes
        );
    }

    public function updatePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->twoFactorSecret = $secret;
        $this->twoFactorConfirmed = null;
    }

    public function confirmTwoFactor(): void
    {
        $this->twoFactorConfirmed = true;
    }

    public function setRecoveryCodes(array $recoveryCodes): void
    {
        $this->twoFactorRecoveryCodes = json_encode($recoveryCodes);
    }

    public function getTwoFactorRecoveryCodes(): ?string
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

}