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
        private string $passwordHash
    ){}

    public static function register(
        string $id,
        string $name,
        EmailAddress $email,
        string $passwordHash
    ): self {
        $user = new self($id, $name, $email, $passwordHash);

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
        string $passwordHash
    ): self {
        // create instance without dispath event
        return new self(
            $id,
            $name,
            new EmailAddress($email),
            $passwordHash
        );
    }

    public function updatePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
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

}