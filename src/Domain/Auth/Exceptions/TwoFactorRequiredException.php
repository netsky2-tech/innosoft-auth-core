<?php

namespace InnoSoft\AuthCore\Domain\Auth\Exceptions;

class TwoFactorRequiredException extends \Exception
{
    public function __construct(public string $userId)
    {
        parent::__construct(trans('auth-core::messages.two_factor_required'), 403);
    }
}