<?php

namespace InnoSoft\AuthCore\Infrastructure\Bus\Event;

use InnoSoft\AuthCore\Domain\Shared\DomainEvent;
use InnoSoft\AuthCore\Domain\Shared\DomainEventBus;
use Illuminate\Contracts\Events\Dispatcher;

readonly class LaravelEventBus implements DomainEventBus
{

    public function __construct(
        private Dispatcher $dispatcher,
    )
    {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);

            // logger()->info("Domain Event Published: " . get_class($event));
        }
    }
}