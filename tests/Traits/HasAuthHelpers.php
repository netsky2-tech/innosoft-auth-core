<?php

namespace InnoSoft\AuthCore\Tests\Traits;

use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User;
use InnoSoft\AuthCore\Database\Seeders\AuthCoreSeeder;
trait HasAuthHelpers
{
    protected function createSuperAdmin(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
    {
        $this->seed(AuthCoreSeeder::class);

        $roleName = config('auth-core.super_admin_role', 'SuperAdmin');
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }
}