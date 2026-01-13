<?php

namespace InnoSoft\AuthCore\Application\Roles\Commands;

final readonly class RevokeRolePermissionCommand
{
    public function __construct(
        public string $roleName,
        public string $permissionName,
        public string $guardName = 'api'
    ) {}
}
