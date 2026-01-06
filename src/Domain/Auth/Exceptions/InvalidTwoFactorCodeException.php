<?php

namespace InnoSoft\AuthCore\Domain\Auth\Exceptions;

class InvalidTwoFactorCodeException extends \Exception
{
    public function __construct()
    {
        parent::__construct("The provided two factor authentication code was invalid.", 422);
    }
}