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
        private ?string $twoFactorSecret = null,
        private ?bool $twoFactorConfirmed = false,
    ){}

    public static function register(
        string $id,
        string $name,
        EmailAddress $email,
        string $passwordHash,
        ?string $twoFactorSecret = null,
        ?bool $twoFactorConfirmed = false
    ): self {
        $user = new self($id, $name, $email, $passwordHash, $twoFactorSecret, $twoFactorConfirmed);

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
        ?string $twoFactorSecret = null,
        bool $twoFactorConfirmed = false
    ): self {
        // create instance without dispath event
        return new self(
            $id,
            $name,
            new EmailAddress($email),
            $passwordHash,
            $twoFactorSecret,
            $twoFactorConfirmed
        );
    }

    public function updatePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->twoFactorSecret = $secret;
        $this->twoFactorConfirmed = false;
    }

    public function confirmTwoFactor(): void
    {
        $this->twoFactorConfirmed = true;
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