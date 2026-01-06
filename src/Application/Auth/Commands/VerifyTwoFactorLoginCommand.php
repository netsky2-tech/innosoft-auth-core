<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

readonly class VerifyTwoFactorLoginCommand
{
    public function __construct(
        public string $challengeToken,
        public string $code,
        public string $deviceName
    ) {}
}
