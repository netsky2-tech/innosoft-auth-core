<?php

namespace InnoSoft\AuthCore\Application\Users\DTOs;

use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use Illuminate\Database\Eloquent\Model;

final readonly class UserView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public bool $twoFactorConfirmed,
        public ?string $createdAt,
        public ?string $updatedAt = null
    ) {}

    public static function fromDomain(User|Model $user): self
    {
        if ($user instanceof Model) {
            return new self(
                id: (string) $user->id,
                name: $user->name,
                email: $user->email,
                twoFactorConfirmed: (bool) $user->two_factor_confirmed_at,
                createdAt: $user->created_at?->format(\DateTimeInterface::ATOM),
                updatedAt: $user->updated_at?->format(\DateTimeInterface::ATOM)
            );
        }

        return new self(
            id: $user->getId(),
            name: $user->getName(),
            email: $user->getEmail()->getValue(),
            twoFactorConfirmed: $user->getTwoFactorConfirmed(),
            createdAt: $user->getCreatedAt()?->format(\DateTimeInterface::ATOM)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'two_factor_confirmed' => $this->twoFactorConfirmed,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
    
    // Magic getter to allow property access like $view->two_factor_confirmed
    public function __get($name)
    {
        if ($name === 'two_factor_confirmed') {
            return $this->twoFactorConfirmed;
        }
        if ($name === 'created_at') {

            return $this->createdAt ? \Illuminate\Support\Carbon::parse($this->createdAt) : null;
        }
        if ($name === 'updated_at') {
            return $this->updatedAt ? \Illuminate\Support\Carbon::parse($this->updatedAt) : null;
        }
        return $this->{$name} ?? null;
    }
}
