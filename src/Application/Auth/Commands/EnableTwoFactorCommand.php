<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;


readonly class EnableTwoFactorCommand
{
    public function __construct(
        public string $userId
    ) {}
}