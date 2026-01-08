<?php

namespace InnoSoft\AuthCore\Tests\Traits;

use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User;

trait CreatesUsers
{
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }
}
