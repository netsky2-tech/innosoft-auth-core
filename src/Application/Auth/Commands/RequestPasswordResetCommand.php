<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

final readonly class RequestPasswordResetCommand
{
    public function __construct(public string $email) {}
}