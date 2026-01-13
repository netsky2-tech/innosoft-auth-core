<?php

namespace InnoSoft\AuthCore\Domain\Permissions\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class PermissionDeleted implements DomainEvent
{
    public function __construct(
        private string            $permissionName,
        private string            $guardName,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function permissionName(): string { return $this->permissionName; }
    public function guardName(): string { return $this->guardName; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
