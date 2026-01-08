<?php

namespace InnoSoft\AuthCore\Domain\Auth\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class UserLoggedIn implements DomainEvent
{
    public function __construct(
        private string            $userId,
        private string            $ipAddress,
        private string            $userAgent,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function userId(): string { return $this->userId; }
    public function ipAddress(): string { return $this->ipAddress; }
    public function userAgent(): string { return $this->userAgent; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
