<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

class TwoFactorDisabled
{
    public function __construct(public string $userId) {}
}