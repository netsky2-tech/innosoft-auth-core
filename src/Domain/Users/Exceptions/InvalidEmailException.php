<?php

namespace InnoSoft\AuthCore\Domain\Users\Exceptions;

use InnoSoft\AuthCore\Domain\Shared\DomainException;
class InvalidEmailException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid email provided', 401);
    }
}