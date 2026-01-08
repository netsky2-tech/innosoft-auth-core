<?php

namespace InnoSoft\AuthCore\Application\Roles\Commands;

final readonly class SyncRolePermissionsCommand
{
    public function __construct(
        public string $roleName,
        public array $permissions,
        public string $guardName = 'api'
    ) {}
}
