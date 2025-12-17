<?php

namespace InnoSoft\AuthCore\Application\Roles\Commands;

readonly class CreateRoleCommand
{
    public function __construct(
        public string $name,
        public array $permissions = [],
        public string $guardName = 'api'
    ) {}
}