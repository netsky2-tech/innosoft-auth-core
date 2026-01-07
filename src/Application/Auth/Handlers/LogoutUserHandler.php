<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use InnoSoft\AuthCore\Application\Auth\Commands\LogoutUserCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\DeviceSessionProvider;

final readonly class LogoutUserHandler
{
    public function __construct(
        private DeviceSessionProvider $sessionProvider
    ) {}

    public function handle(LogoutUserCommand $command): void
    {
        // Revoke the current session (token)
        $this->sessionProvider->revokeSession($command->userId, $command->sessionId);
    }
}
