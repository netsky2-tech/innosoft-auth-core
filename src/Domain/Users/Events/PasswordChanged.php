<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class PasswordChanged implements DomainEvent
{

    public function __construct(
        private string $userId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ){}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}