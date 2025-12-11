<?php

namespace InnoSoft\AuthCore\Domain\Shared;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}