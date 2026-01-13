<?php

namespace InnoSoft\AuthCore\Domain\Permissions\Exceptions;

use Exception;

class PermissionNotFoundException extends Exception
{
    public function __construct(string $message = "Permission not found.")
    {
        parent::__construct(trans('auth-core::messages.permission_not_found', ['permission' => $message]));
    }
}
