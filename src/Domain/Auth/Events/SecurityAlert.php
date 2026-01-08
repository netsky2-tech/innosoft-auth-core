<?php

namespace InnoSoft\AuthCore\Domain\Auth\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class SecurityAlert implements DomainEvent
{
    public function __construct(
        private string            $threatType,
        private string            $ipAddress,
        private ?string           $userId = null,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function threatType(): string { return $this->threatType; }
    public function ipAddress(): string { return $this->ipAddress; }
    public function userId(): ?string { return $this->userId; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
