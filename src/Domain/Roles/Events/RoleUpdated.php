<?php

namespace InnoSoft\AuthCore\Domain\Roles\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class RoleUpdated implements DomainEvent
{
    public function __construct(
        private string            $roleId,
        private string            $oldName,
        private string            $newName,
        private string            $guardName,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function roleId(): string { return $this->roleId; }
    public function oldName(): string { return $this->oldName; }
    public function newName(): string { return $this->newName; }
    public function guardName(): string { return $this->guardName; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
