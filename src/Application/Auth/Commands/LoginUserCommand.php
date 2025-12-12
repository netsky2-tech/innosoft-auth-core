<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

final readonly class LoginUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName = 'unknown'
    ) {}
}