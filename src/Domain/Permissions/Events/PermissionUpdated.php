<?php

namespace InnoSoft\AuthCore\Domain\Permissions\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class PermissionUpdated implements DomainEvent
{
    public function __construct(
        private string            $permissionId,
        private string            $oldName,
        private string            $newName,
        private string            $guardName,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function permissionId(): string { return $this->permissionId; }
    public function oldName(): string { return $this->oldName; }
    public function newName(): string { return $this->newName; }
    public function guardName(): string { return $this->guardName; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
