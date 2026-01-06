<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

readonly class ConfirmTwoFactorCommand
{
    public function __construct(
        public string $userId,
        public string $code
    ) {}
}
