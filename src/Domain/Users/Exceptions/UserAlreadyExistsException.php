<?php
namespace InnoSoft\AuthCore\Domain\Users\Exceptions;
use Exception;

class UserAlreadyExistsException extends Exception
{
    public function __construct(string $email)
    {
        parent::__construct("User with email {$email} already exists.", 409); // 409 Conflict
    }
}