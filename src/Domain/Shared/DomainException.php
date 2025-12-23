<?php

namespace InnoSoft\AuthCore\Domain\Shared;

use Exception;
class DomainException extends Exception
{
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }

    public function getErrorCode(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}