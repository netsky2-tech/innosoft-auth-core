<?php

namespace InnoSoft\AuthCore\Domain\Roles\Exceptions;

class RoleAlreadyExists extends \Exception
{
    public function __construct(string $roleName)
    {
        parent::__construct(trans('auth-core::messages.role_already_exists', ['role' => $roleName]));
    }
}