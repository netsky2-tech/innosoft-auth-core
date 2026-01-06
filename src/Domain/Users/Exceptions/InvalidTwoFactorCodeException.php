<?php

namespace InnoSoft\AuthCore\Domain\Users\Exceptions;

class InvalidTwoFactorCodeException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Invalid two factor code provided', 401);
    }
}