<?php

namespace InnoSoft\AuthCore\Application\Auth\Queries\Handlers;

use InnoSoft\AuthCore\Application\Auth\Queries\ListUserSessionsQuery;
use InnoSoft\AuthCore\Domain\Auth\Services\DeviceSessionProvider;

final readonly class ListUserSessionsHandler
{
    public function __construct(
        private DeviceSessionProvider $sessionProvider
    ) {}

    public function handle(ListUserSessionsQuery $query): array
    {
        return $this->sessionProvider->getSessions($query->userId, $query->currentTokenId);
    }
}
