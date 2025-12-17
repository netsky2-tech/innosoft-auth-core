<?php

namespace InnoSoft\AuthCore\Domain\Roles;

readonly class RoleDTO
{
    public function __construct(
        public string $name,
        public string $guardName = 'api',
        public array $permissions = []
    ) {}
}