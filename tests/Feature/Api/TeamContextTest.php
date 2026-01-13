<?php

namespace InnoSoft\AuthCore\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use InnoSoft\AuthCore\Tests\TestCase;
use InnoSoft\AuthCore\Tests\Traits\CreatesUsers;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TeamContextTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable teams feature
        Config::set('auth-core.features.teams', true);
        Config::set('permission.teams', true);
        
        // Clear permission cache to ensure new config is respected
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    #[Test]
    public function it_can_list_user_teams()
    {
        $user = $this->createEloquentUser();
        
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(route('api.v1.teams.index'));

        $response->assertStatus(200)
                 ->assertJsonStructure([]);
    }

    #[Test]
    public function it_fails_to_switch_team_if_user_does_not_belong_to_it()
    {
        $user = $this->createEloquentUser();
        Sanctum::actingAs($user, ['*']);

        // Ensure validator returns false (default behavior of HostTeamMembershipValidator for test user)
        // Or we can mock it to be explicit
        $this->mock(\InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator::class, function ($mock) {
            $mock->shouldReceive('validate')->andReturn(false);
        });

        $response = $this->postJson(route('api.v1.teams.switch', ['id' => '999']));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['team_id']);
    }

    #[Test]
    public function it_can_switch_team_if_validation_passes()
    {
        // Mock the TeamMembershipValidator to return true
        $this->mock(\InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator::class, function ($mock) {
            $mock->shouldReceive('validate')->andReturn(true);
        });

        $user = $this->createEloquentUser();
        Sanctum::actingAs($user, ['*']);

        $teamId = '123';
        $response = $this->postJson(route('api.v1.teams.switch', ['id' => $teamId]));

        // The ApiResponse trait merges access_token at the root level if present
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'team_id'
                 ]);
                 
        $this->assertEquals($teamId, $response->json('team_id'));
    }

    #[Test]
    public function middleware_sets_team_id_in_permissions_registrar()
    {
        // Mock the validator to allow access to the team
        $this->mock(\InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator::class, function ($mock) {
            $mock->shouldReceive('validate')->andReturn(true);
        });

        $user = $this->createEloquentUser();
        
        // Use an integer ID to match the unsignedBigInteger column in the test DB schema
        $teamId = 555;
        
        $roleName = config('auth-core.super_admin_role', 'SuperAdmin');
        
        // Create role specifically for this team
        // We use forceCreate to bypass any mass assignment protection issues if they arise, though create() should work.
        // We ensure team_id is set.
        if (!Role::where('name', $roleName)->where('team_id', $teamId)->exists()) {
            Role::create(['name' => $roleName, 'guard_name' => 'api', 'team_id' => $teamId]);
        }
        
        // Retrieve the specific role instance
        $role = Role::where('name', $roleName)->where('team_id', $teamId)->first();
        
        // Assign role to user. 
        // When teams are enabled, Spatie expects us to set the team context before assigning, 
        // OR passing the role object with team_id should handle it.
        // Let's set the context just to be sure assignRole works as expected for that team.
        setPermissionsTeamId($teamId);
        $user->assignRole($role);
        
        // Reset context to null to simulate a fresh request coming in
        setPermissionsTeamId(null);

        Sanctum::actingAs($user, ['*']);
        
        // The middleware should pick up the header, set the context to 555, 
        // and then the Gate check should find the role we just assigned for team 555.
        $response = $this->withHeaders(['X-Team-ID' => (string) $teamId])
                         ->getJson(route('api.v1.users.index'));
                         
        $response->assertStatus(200);
    }
}
