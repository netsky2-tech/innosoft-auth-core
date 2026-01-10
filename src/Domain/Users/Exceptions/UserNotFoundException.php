<?php
namespace InnoSoft\AuthCore\Domain\Users\Exceptions;
use InnoSoft\AuthCore\Domain\Shared\DomainException;

class UserNotFoundException extends DomainException
{
    public function __construct(string $identifier)
    {
        // Check if identifier looks like an email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
             parent::__construct(trans('auth-core::messages.user_not_found', ['email' => $identifier]), 404);
        } else {
             parent::__construct(trans('auth-core::messages.user_not_found_id', ['id' => $identifier]), 404);
        }
    }
}