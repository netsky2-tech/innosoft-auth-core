<?php

namespace InnoSoft\AuthCore\Application\Permissions\Commands;

final readonly class CreatePermissionCommand
{
    public function __construct(
        public string $name,
        public string $guardName = 'api'
    ) {}
}
