<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Bus;
use InnoSoft\AuthCore\Application\Permissions\Commands\CreatePermissionCommand;
use InnoSoft\AuthCore\Application\Permissions\Commands\DeletePermissionCommand;
use InnoSoft\AuthCore\Application\Permissions\Commands\UpdatePermissionCommand;
use InnoSoft\AuthCore\Application\Permissions\Queries\GetPermissionsQuery;
use InnoSoft\AuthCore\Application\Permissions\Queries\Handlers\GetPermissionsHandler;

class PermissionController extends Controller
{
    public function index(Request $request, GetPermissionsHandler $handler): JsonResponse
    {
        $query = new GetPermissionsQuery(
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
            'guard_name' => 'string|max:255'
        ]);

        $command = new CreatePermissionCommand(
            name: $request->input('name'),
            guardName: $request->input('guard_name', 'api')
        );

        Bus::dispatch($command);

        return response()->json(['message' => 'Permission created successfully'], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'string|max:255'
        ]);

        $command = new UpdatePermissionCommand(
            permissionId: $id,
            newName: $request->input('name'),
            guardName: $request->input('guard_name', 'api')
        );

        Bus::dispatch($command);

        return response()->json(['message' => 'Permission updated successfully']);
    }

    public function destroy(string $id): JsonResponse
    {
        $command = new DeletePermissionCommand($id);
        Bus::dispatch($command);

        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
