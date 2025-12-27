<?php

namespace InnoSoft\AuthCore\Application\Users\Commands;

readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

}