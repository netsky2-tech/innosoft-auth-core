<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

readonly class DisableTwoFactorCommand
{
    public function __construct(
        public string $userId,
        public string $currentPassword
    ) {}
}
