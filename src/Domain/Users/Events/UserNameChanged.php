<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class UserNameChanged implements DomainEvent
{
    public function __construct(
        private string            $userId,
        private string            $name,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
    public function user(): string { return $this->userId; }
    public function email(): string { return $this->name; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}