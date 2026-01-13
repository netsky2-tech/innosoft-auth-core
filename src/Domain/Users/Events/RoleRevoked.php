<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class RoleRevoked implements DomainEvent
{
    public function __construct(
        private string            $userId,
        private string            $roleId,
        private string            $roleName,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}

    public function userId(): string { return $this->userId; }
    public function roleId(): string { return $this->roleId; }
    public function roleName(): string { return $this->roleName; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
