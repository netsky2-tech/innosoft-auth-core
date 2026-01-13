<?php

namespace InnoSoft\AuthCore\Tests\Feature\Api;

use InnoSoft\AuthCore\Domain\Users\Aggregates\User;
use InnoSoft\AuthCore\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRoleAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'users.assign_role', 'guard_name' => 'api']);
    }

    #[Test]
    public function it_can_assign_role_to_user()
    {
        $admin = $this->authenticateUser(['users.assign_role']);
        $targetUser = $this->createUser();
        $role = Role::create(['name' => 'Editor', 'guard_name' => 'api']);

        $response = $this->postJson(route('api.v1.users.roles.assign', $targetUser->getId()), [
            'role' => 'Editor',
            'guard_name' => 'api'
        ]);

        $response->assertOk();
        
        // Verify via Spatie model (Infrastructure check)
        $eloquentUser = \InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User::find($targetUser->getId());
        $this->assertTrue($eloquentUser->hasRole('Editor'));
    }

    #[Test]
    public function it_can_revoke_role_from_user()
    {
        $admin = $this->authenticateUser(['users.assign_role']);
        $targetUser = $this->createUser();
        $role = Role::create(['name' => 'Editor', 'guard_name' => 'api']);
        
        // Assign first
        $eloquentUser = \InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User::find($targetUser->getId());
        $eloquentUser->assignRole('Editor');

        $response = $this->deleteJson(route('api.v1.users.roles.revoke', $targetUser->getId()), [
            'role' => 'Editor',
            'guard_name' => 'api'
        ]);

        $response->assertOk();
        $this->assertFalse($eloquentUser->refresh()->hasRole('Editor'));
    }

    #[Test]
    public function it_fails_if_role_does_not_exist()
    {
        $this->authenticateUser(['users.assign_role']);
        $targetUser = $this->createUser();

        $response = $this->postJson(route('api.v1.users.roles.assign', $targetUser->getId()), [
            'role' => 'NonExistentRole',
            'guard_name' => 'api'
        ]);

        $response->assertStatus(500); // Or 404 depending on exception handling
    }
}
