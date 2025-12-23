<?php

namespace InnoSoft\AuthCore\Domain\Users\Exceptions;
use InnoSoft\AuthCore\Domain\Shared\DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials provided', 401);
    }
}