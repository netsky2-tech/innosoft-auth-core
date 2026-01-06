<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

readonly class TwoFactorEnrollmentConfirmed implements DomainEvent
{
    public function __construct(
        private string $userId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}