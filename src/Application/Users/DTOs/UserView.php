<?php

namespace InnoSoft\AuthCore\Application\Users\DTOs;

use InnoSoft\AuthCore\Domain\Users\Aggregates\User;

final readonly class UserView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $createdAt
    ) {}

    public static function fromDomain(User $user): self
    {
        return new self(
            id: $user->getId(),
            name: $user->getName(),
            email: $user->getEmail()->getValue(),
            createdAt: $user->getCreatedAt()?->format(\DateTimeInterface::ATOM)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt
        ];
    }
}