<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Bus;
use InnoSoft\AuthCore\Application\Roles\Commands\CreateRoleCommand;
use InnoSoft\AuthCore\Application\Roles\Commands\DeleteRoleCommand;
use InnoSoft\AuthCore\Application\Roles\Commands\GivePermissionToRoleCommand;
use InnoSoft\AuthCore\Application\Roles\Commands\RevokeRolePermissionCommand;
use InnoSoft\AuthCore\Application\Roles\Commands\SyncRolePermissionsCommand;
use InnoSoft\AuthCore\Application\Roles\Commands\UpdateRoleCommand;
use InnoSoft\AuthCore\Application\Roles\Queries\GetRolesQuery;
use InnoSoft\AuthCore\Application\Roles\Queries\Handlers\GetRolesHandler;

class RoleController extends Controller
{
    public function index(Request $request, GetRolesHandler $handler): JsonResponse
    {
        $query = new GetRolesQuery(
            search: $request->input('search'),
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15)
        );

        $result = $handler->handle($query);

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string',
            'guard_name' => 'string|max:255'
        ]);

        $command = new CreateRoleCommand(
            name: $request->input('name'),
            permissions: $request->input('permissions', []),
            guardName: $request->input('guard_name', 'api')
        );

        Bus::dispatch($command);

        return response()->json(['message' => 'Role created successfully'], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'string|max:255'
        ]);

        $command = new UpdateRoleCommand(
            roleId: $id,
            newName: $request->input('name'),
            guardName: $request->input('guard_name', 'api')
        );

        Bus::dispatch($command);

        return response()->json(['message' => 'Role updated successfully']);
    }

    public function destroy(string $id): JsonResponse
    {
        $command = new DeleteRoleCommand($id);
        Bus::dispatch($command);

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function syncPermissions(Request $request, string $name): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
            'guard_name' => 'string|max:255'
        ]);

        $command = new SyncRolePermissionsCommand(
            roleName: $name,
            permissions: $request->input('permissions'),
            guardName: $request->input('guard_name', 'api')
        );

        Bus::dispatch($command);

        return response()->json(['message' => 'Permissions synced successfully']);
    }

    public function givePermission(Request $request, string $name): JsonResponse
    {
        $request->validate([
            'permission' => 'required|string',
            'guard_name' => 'string|max:255'
        ]);

        $command = new GivePermissionToRoleCommand(
            roleName: $name,
            permissionName: $request->input('permission'),
            guardName: $request->input('guard_name', 'api')
        );

        Bus::dispatch($command);

        return response()->json(['message' => 'Permission added to role successfully']);
    }

    public function revokePermission(Request $request, string $name): JsonResponse
    {
        $request->validate([
            'permission' => 'required|string',
            'guard_name' => 'string|max:255'
        ]);

        $command = new RevokeRolePermissionCommand(
            roleName: $name,
            permissionName: $request->input('permission'),
            guardName: $request->input('guard_name', 'api')
        );

        Bus::dispatch($command);

        return response()->json(['message' => 'Permission revoked from role successfully']);
    }
}
