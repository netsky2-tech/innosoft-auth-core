<?php
namespace InnoSoft\AuthCore\Domain\Users\Exceptions;
use InnoSoft\AuthCore\Domain\Shared\DomainException;

class UserNotFoundException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("User with email {$email} does not exist.", 404);
    }
}