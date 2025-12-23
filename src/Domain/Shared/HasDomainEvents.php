<?php

namespace InnoSoft\AuthCore\Domain\Shared;


trait HasDomainEvents
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /**
     * Registra un evento de dominio para su posterior publicación.
     *
     * @param DomainEvent $event
     * @return void
     */
    public function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Retorna todos los eventos registrados y LIMPIA el array.
     *
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;

        $this->domainEvents = [];

        return $events;
    }
}