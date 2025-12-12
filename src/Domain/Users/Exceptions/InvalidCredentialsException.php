<?php

namespace InnoSoft\AuthCore\Domain\Users\Exceptions;
use Exception;
class InvalidCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid credentials provided', 401);
    }
}