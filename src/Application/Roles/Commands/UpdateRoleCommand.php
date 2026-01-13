<?php

namespace InnoSoft\AuthCore\Application\Roles\Commands;

final readonly class UpdateRoleCommand
{
    public function __construct(
        public string $roleId,
        public string $newName,
        public string $guardName = 'api'
    ) {}
}
