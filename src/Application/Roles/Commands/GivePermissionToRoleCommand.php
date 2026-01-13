<?php

namespace InnoSoft\AuthCore\Application\Roles\Commands;

final readonly class GivePermissionToRoleCommand
{
    public function __construct(
        public string $roleName,
        public string $permissionName,
        public string $guardName = 'api'
    ) {}
}
