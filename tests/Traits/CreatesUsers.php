<?php

namespace InnoSoft\AuthCore\Tests\Traits;

use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User;

trait CreatesUsers
{
    protected function createEloquentUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }
}
