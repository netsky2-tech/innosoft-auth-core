<?php

namespace InnoSoft\AuthCore\Application\Teams\Queries;

final readonly class ListUserTeamsQuery
{
    public function __construct(
        public string $userId
    ) {}
}
