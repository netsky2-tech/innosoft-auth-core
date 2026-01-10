<?php

namespace InnoSoft\AuthCore\Domain\Roles\Exceptions;

use Exception;

class RoleNotFoundException extends Exception
{
    public function __construct(string $roleName)
    {
        parent::__construct(trans('auth-core::messages.role_not_found', ['role' => $roleName]));
    }
}
