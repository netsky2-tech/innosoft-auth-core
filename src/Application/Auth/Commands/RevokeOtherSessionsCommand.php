<?php

namespace InnoSoft\AuthCore\Application\Auth\Commands;

readonly class RevokeOtherSessionsCommand
{
    public function __construct(
        public string $userId,
        public string $currentTokenId
    ) {}
}
