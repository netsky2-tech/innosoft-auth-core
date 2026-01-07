<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

readonly class LogoutUserCommand
{
    public function __construct(
        public string $userId,
        public string $sessionId
    ) {}
}
