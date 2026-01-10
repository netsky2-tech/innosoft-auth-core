<?php

namespace InnoSoft\AuthCore\Domain\Users\Exceptions;

class InvalidTwoFactorCodeException extends \Exception
{
    public function __construct()
    {
        parent::__construct(trans('auth-core::messages.invalid_two_factor_code'), 401);
    }
}