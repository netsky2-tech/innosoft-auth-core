<?php

namespace InnoSoft\AuthCore\Domain\Shared;

interface DomainEventBus
{
    public function publish(DomainEvent ...$events): void;
}