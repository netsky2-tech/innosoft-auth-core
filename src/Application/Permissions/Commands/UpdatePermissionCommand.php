<?php

namespace InnoSoft\AuthCore\Application\Permissions\Commands;

final readonly class UpdatePermissionCommand
{
    public function __construct(
        public string $permissionId,
        public string $newName,
        public string $guardName = 'api'
    ) {}
}
