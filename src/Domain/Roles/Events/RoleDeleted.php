<?php

namespace InnoSoft\AuthCore\Domain\Roles\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class RoleDeleted implements DomainEvent
{
    public function __construct(
        private string            $roleName,
        private string            $guardName,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function roleName(): string { return $this->roleName; }
    public function guardName(): string { return $this->guardName; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
