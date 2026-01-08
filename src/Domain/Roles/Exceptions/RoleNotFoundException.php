<?php

namespace InnoSoft\AuthCore\Domain\Roles\Exceptions;

use Exception;

class RoleNotFoundException extends Exception
{
    public function __construct(string $message = "Role not found")
    {
        parent::__construct($message);
    }
}
