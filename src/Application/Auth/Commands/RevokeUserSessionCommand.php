<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

readonly class RevokeUserSessionCommand
{
    public function __construct(
        public string $userId,
        public string $sessionId
    ) {}
}
