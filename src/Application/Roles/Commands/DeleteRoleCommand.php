<?php

namespace InnoSoft\AuthCore\Application\Roles\Commands;

final readonly class DeleteRoleCommand
{
    public function __construct(
        public string $roleId
    ) {}
}
