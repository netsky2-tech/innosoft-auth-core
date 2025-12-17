<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
}