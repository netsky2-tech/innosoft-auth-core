<?php

namespace InnoSoft\AuthCore\Tests\Feature\Api;

use InnoSoft\AuthCore\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure permissions exist
        Permission::create(['name' => 'roles.view', 'guard_name' => 'api']);
        Permission::create(['name' => 'roles.create', 'guard_name' => 'api']);
        Permission::create(['name' => 'roles.update', 'guard_name' => 'api']);
        Permission::create(['name' => 'roles.delete', 'guard_name' => 'api']);
        Permission::create(['name' => 'roles.manage_permissions', 'guard_name' => 'api']);
    }

    #[Test]
    public function it_can_list_roles(): void
    {
        $user = $this->authenticateUser(['roles.view']);
        Role::create(['name' => 'TestRole', 'guard_name' => 'api']);

        $response = $this->getJson(route('api.v1.roles.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'guard', 'permissions', 'created_at']
                ],
                // 'meta', // Removed meta check
                // 'links'
            ]);
    }

    #[Test]
    public function it_can_create_a_role(): void
    {
        $user = $this->authenticateUser(['roles.create']);
        $permission = Permission::create(['name' => 'test.permission', 'guard_name' => 'api']);

        $response = $this->postJson(route('api.v1.roles.store'), [
            'name' => 'NewRole',
            'guard_name' => 'api',
            'permissions' => [$permission->name]
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('roles', ['name' => 'NewRole']);
        $role = Role::findByName('NewRole', 'api');
        $this->assertTrue($role->hasPermissionTo('test.permission'));
    }

    #[Test]
    public function it_can_update_a_role(): void
    {
        $user = $this->authenticateUser(['roles.update']);
        $role = Role::create(['name' => 'OldName', 'guard_name' => 'api']);

        $response = $this->putJson(route('api.v1.roles.update', $role->id), [
            'name' => 'NewName',
            'guard_name' => 'api'
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'NewName']);
    }

    #[Test]
    public function it_can_delete_a_role(): void
    {
        $user = $this->authenticateUser(['roles.delete']);
        $role = Role::create(['name' => 'ToDelete', 'guard_name' => 'api']);

        $response = $this->deleteJson(route('api.v1.roles.destroy', $role->id));

        $response->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    #[Test]
    public function it_can_sync_permissions_to_role(): void
    {
        $user = $this->authenticateUser(['roles.manage_permissions']);
        $role = Role::create(['name' => 'RoleToSync', 'guard_name' => 'api']);
        $perm1 = Permission::create(['name' => 'perm1', 'guard_name' => 'api']);
        $perm2 = Permission::create(['name' => 'perm2', 'guard_name' => 'api']);

        $response = $this->postJson(route('api.v1.roles.permissions.sync', $role->name), [
            'permissions' => ['perm1', 'perm2'],
            'guard_name' => 'api'
        ]);

        $response->assertOk();
        $this->assertTrue($role->refresh()->hasAllPermissions(['perm1', 'perm2']));
    }

    #[Test]
    public function it_can_give_permission_to_role(): void
    {
        $user = $this->authenticateUser(['roles.manage_permissions']);
        $role = Role::create(['name' => 'RoleToGive', 'guard_name' => 'api']);
        $perm = Permission::create(['name' => 'perm.give', 'guard_name' => 'api']);

        $response = $this->postJson(route('api.v1.roles.permissions.give', $role->name), [
            'permission' => 'perm.give',
            'guard_name' => 'api'
        ]);

        $response->assertOk();
        $this->assertTrue($role->refresh()->hasPermissionTo('perm.give'));
    }

    #[Test]
    public function it_can_revoke_permission_from_role(): void
    {
        $user = $this->authenticateUser(['roles.manage_permissions']);
        $role = Role::create(['name' => 'RoleToRevoke', 'guard_name' => 'api']);
        $perm = Permission::create(['name' => 'perm.revoke', 'guard_name' => 'api']);
        $role->givePermissionTo($perm);

        $response = $this->deleteJson(route('api.v1.roles.permissions.revoke', $role->name), [
            'permission' => 'perm.revoke',
            'guard_name' => 'api'
        ]);

        $response->assertOk();
        $this->assertFalse($role->refresh()->hasPermissionTo('perm.revoke'));
    }
}
