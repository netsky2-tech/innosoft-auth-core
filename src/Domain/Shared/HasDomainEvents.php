<?php

namespace InnoSoft\AuthCore\Domain\Shared;

trait HasDomainEvents
{
    private array $events = [];

    protected function record(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}