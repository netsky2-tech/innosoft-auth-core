<?php

namespace InnoSoft\AuthCore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User;
use InnoSoft\AuthCore\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_assign_role_to_user(): void
    {
        $this->authenticateUser(['users.assign_role']);
        $targetUser = User::factory()->create();
        Role::create(['name' => 'Manager', 'guard_name' => 'api']);

        $response = $this->postJson(route('api.v1.users.roles.assign', $targetUser->id), [
            'role' => 'Manager',
            'guard_name' => 'api'
        ]);

        $response->assertStatus(200);
        $this->assertTrue($targetUser->refresh()->hasRole('Manager'));
    }

    #[Test]
    public function it_can_revoke_role_from_user(): void
    {
        $this->authenticateUser(['users.assign_role']);
        $targetUser = User::factory()->create();
        $role = Role::create(['name' => 'Manager', 'guard_name' => 'api']);
        $targetUser->assignRole($role);

        $response = $this->deleteJson(route('api.v1.users.roles.revoke', $targetUser->id), [
            'role' => 'Manager',
            'guard_name' => 'api'
        ]);

        $response->assertStatus(200);
        $this->assertFalse($targetUser->refresh()->hasRole('Manager'));
    }

    #[Test]
    public function it_cannot_assign_non_existent_role(): void
    {
        $this->authenticateUser(['users.assign_role']);
        $targetUser = User::factory()->create();

        $response = $this->postJson(route('api.v1.users.roles.assign', $targetUser->id), [
            'role' => 'GhostRole',
            'guard_name' => 'api'
        ]);

        $response->assertStatus(500); // Or 404 depending on exception handling
    }
}
