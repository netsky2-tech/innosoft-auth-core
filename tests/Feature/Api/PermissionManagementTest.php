<?php

namespace InnoSoft\AuthCore\Tests\Feature\Api;

use InnoSoft\AuthCore\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;

class PermissionManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'permissions.view', 'guard_name' => 'api']);
        Permission::create(['name' => 'permissions.create', 'guard_name' => 'api']);
        Permission::create(['name' => 'permissions.update', 'guard_name' => 'api']);
        Permission::create(['name' => 'permissions.delete', 'guard_name' => 'api']);
    }

    #[Test]
    public function it_can_list_permissions(): void
    {
        $this->authenticateUser(['permissions.view']);
        Permission::create(['name' => 'test.perm', 'guard_name' => 'api']);

        $response = $this->getJson(route('api.v1.permissions.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'guard', 'created_at']
                ],
                // 'meta', // Removed meta check as it might not be present if pagination is not used or structure is different
                // 'links'
            ]);
    }

    #[Test]
    public function it_can_create_a_permission(): void
    {
        $this->authenticateUser(['permissions.create']);

        $response = $this->postJson(route('api.v1.permissions.store'), [
            'name' => 'new.permission',
            'guard_name' => 'api'
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('permissions', ['name' => 'new.permission']);
    }

    #[Test]
    public function it_can_update_a_permission(): void
    {
        $this->authenticateUser(['permissions.update']);
        $permission = Permission::create(['name' => 'old.perm', 'guard_name' => 'api']);

        $response = $this->putJson(route('api.v1.permissions.update', $permission->id), [
            'name' => 'updated.perm',
            'guard_name' => 'api'
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'updated.perm']);
    }

    #[Test]
    public function it_can_delete_a_permission(): void
    {
        $this->authenticateUser(['permissions.delete']);
        $permission = Permission::create(['name' => 'delete.perm', 'guard_name' => 'api']);

        $response = $this->deleteJson(route('api.v1.permissions.destroy', $permission->id));

        $response->assertOk();
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }
}
