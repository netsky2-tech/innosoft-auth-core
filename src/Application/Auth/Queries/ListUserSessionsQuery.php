<?php

namespace InnoSoft\AuthCore\Application\Auth\Queries;

readonly class ListUserSessionsQuery
{
    public function __construct(
        public string $userId,
        public ?string $currentTokenId = null
    ) {}
}
