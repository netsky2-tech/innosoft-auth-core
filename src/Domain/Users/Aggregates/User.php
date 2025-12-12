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
        private readonly string $passwordHash
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