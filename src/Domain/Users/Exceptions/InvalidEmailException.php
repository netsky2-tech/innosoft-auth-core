<?php

namespace InnoSoft\AuthCore\Domain\Users\Exceptions;

use InnoSoft\AuthCore\Domain\Shared\DomainException;
class InvalidEmailException extends DomainException
{
    public function __construct(string $message = null)
    {
        parent::__construct($message ?? trans('auth-core::messages.invalid_email'), 401);
    }
}