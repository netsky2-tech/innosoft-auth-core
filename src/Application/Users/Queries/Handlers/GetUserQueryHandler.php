<?php

namespace InnoSoft\AuthCore\Application\Users\Queries\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use InnoSoft\AuthCore\Application\Users\Queries\GetUserQuery;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;

final class GetUserQueryHandler
{
    public function __invoke(GetUserQuery $query)
    {
        $user = User::find($query->userId);

        if (!$user) {
            throw new ModelNotFoundException("User with ID {$query->userId} not found.");
        }

        return $user;
    }
}