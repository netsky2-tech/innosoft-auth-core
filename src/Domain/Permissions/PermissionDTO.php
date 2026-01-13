<?php

namespace InnoSoft\AuthCore\Domain\Permissions;

final readonly class PermissionDTO
{
    public function __construct(
        public string $name,
        public string $guardName = 'api'
    ) {}
}
