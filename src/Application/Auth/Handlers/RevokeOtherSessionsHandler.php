<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use InnoSoft\AuthCore\Application\Auth\Commands\RevokeOtherSessionsCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\DeviceSessionProvider;

final readonly class RevokeOtherSessionsHandler
{
    public function __construct(
        private DeviceSessionProvider $sessionProvider
    ) {}

    public function handle(RevokeOtherSessionsCommand $command): void
    {
        $this->sessionProvider->revokeOthers($command->userId, $command->currentTokenId);
    }
}
