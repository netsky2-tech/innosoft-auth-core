<?php

namespace InnoSoft\AuthCore\Domain\Permissions\Exceptions;

use Exception;

class PermissionAlreadyExists extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct(trans('auth-core::messages.permission_already_exists', ['permission' => $name]));
    }
}
