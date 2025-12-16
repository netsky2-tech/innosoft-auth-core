<?php

namespace InnoSoft\AuthCore\Domain\Users\Events;

class TwoFactorEnabled
{
    public function __construct(public string $userId) {}
}