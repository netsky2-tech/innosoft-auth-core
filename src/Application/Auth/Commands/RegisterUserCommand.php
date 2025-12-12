<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {}
}