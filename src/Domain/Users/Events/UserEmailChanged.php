<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

use DateTimeImmutable;
use InnoSoft\AuthCore\Domain\Shared\DomainEvent;

final readonly class UserEmailChanged implements DomainEvent
{
    public function __construct(
        private string            $userId,
        private string            $email,
        private string            $oldEmail,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
    public function user(): string { return $this->userId; }
    public function email(): string { return $this->email; }
    public function oldEmail(): string { return $this->oldEmail; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}