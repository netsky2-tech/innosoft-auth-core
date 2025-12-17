<?php

namespace InnoSoft\AuthCore\Application\Users\Queries;

final readonly class GetUserQuery
{
    public function __construct(
        public string $userId
    ) {}
}