<?php

namespace InnoSoft\AuthCore\Application\Permissions\Commands;

final readonly class DeletePermissionCommand
{
    public function __construct(
        public string $permissionId
    ) {}
}
