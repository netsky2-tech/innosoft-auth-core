<?php

namespace InnoSoft\AuthCore\Domain\Auth\Exceptions;

class TwoFactorRequiredException extends \Exception
{
    public function __construct(public string $userId)
    {
        parent::__construct('Two-factor authentication required', 403);
    }
}