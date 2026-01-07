<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use InnoSoft\AuthCore\Application\Auth\Commands\RevokeUserSessionCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\DeviceSessionProvider;

final readonly class RevokeUserSessionHandler
{
    public function __construct(
        private DeviceSessionProvider $sessionProvider
    ) {}

    public function handle(RevokeUserSessionCommand $command): void
    {
        $this->sessionProvider->revokeSession($command->userId, $command->sessionId);
    }
}
