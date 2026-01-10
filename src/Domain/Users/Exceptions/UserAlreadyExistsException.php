<?php
namespace InnoSoft\AuthCore\Domain\Users\Exceptions;
use InnoSoft\AuthCore\Domain\Shared\DomainException;

class UserAlreadyExistsException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(trans('auth-core::messages.user_already_exists', ['email' => $email]), 409); // 409 Conflict
    }
}