<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InnoSoft\AuthCore\Application\Users\Commands\AssignRoleToUserCommand;
use InnoSoft\AuthCore\Application\Users\Commands\CreateUserCommand;
use InnoSoft\AuthCore\Application\Users\Commands\DeleteUserCommand;
use InnoSoft\AuthCore\Application\Users\Commands\RevokeRoleFromUserCommand;
use InnoSoft\AuthCore\Application\Users\Commands\UpdateUserCommand;
use InnoSoft\AuthCore\Application\Users\Queries\GetUserQuery;
use InnoSoft\AuthCore\Application\Users\Queries\ListUsersQuery;
use InnoSoft\AuthCore\UI\Http\Requests\User\ListUsersRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\CreateUserRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\UpdateUserRequest;
use InnoSoft\AuthCore\UI\Http\Resources\UserResource;
use InnoSoft\AuthCore\UI\Http\Responses\ApiResponse;
use InnoSoft\AuthCore\UI\Http\Traits\HandlesApiExecution;

class UserController extends Controller
{
    use ApiResponse, HandlesApiExecution;
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ){
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['store']);
        $this->middleware('permission:users.update')->only(['update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
        $this->middleware('permission:users.assign_role')->only(['assignRole', 'revokeRole']);
    }

    /**
     * Creates a new user in the system.
     * Dispatches a command to handle user creation logic including password hashing.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            $command = new CreateUserCommand(
                name: $request->validated('name'),
                email: $request->validated('email'),
                password: $request->validated('password')
            );

            // Dispatch the command
            $user = $this->dispatcher->dispatch($command);

            return $this->successResponse(new UserResource($user));
        }, trans('auth-core::messages.user_created_successfully'), 200);
    }

    /**
     * Updates an existing user's information.
     * Handles partial updates for name, email, or password via the command handler.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $id) {
            $command = new UpdateUserCommand(
                $id,
                name: $request->validated('name'),
                email: $request->validated('email'),
                password: $request->validated('password')
            );

            return $this->dispatcher->dispatch($command);

            //return $this->successResponse(new UserResource($user));
        }, trans('auth-core::messages.user_updated_successfully'), 200);
    }

    /**
     * Soft deletes a user from the system.
     * This action marks the user as inactive but retains the data.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->safeExecute(function () use ($id) {

            $command = new DeleteUserCommand(userId: $id);

            $this->dispatcher->dispatch($command);

            return $this->successResponse();
        }, trans('auth-core::messages.user_deleted_successfully'), 204);

    }

    /**
     * Retrieves detailed information for a specific user.
     * Uses a query object to fetch read-optimized data.
     */
    public function show(string $id): JsonResponse
    {
        return $this->safeExecute(function () use ($id) {
            $query = new GetUserQuery(userId: $id);

            $user = $this->dispatcher->dispatch($query);

            return $this->successResponse(new UserResource($user));
        }, trans('auth-core::messages.user_retrieved_successfully'), 200);

    }

    /**
     * Lists users with pagination, sorting, and filtering.
     * Optimized for data grids and administrative views.
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            $query = new ListUsersQuery(
                page: $request->validated('page', 1),
                perPage: $request->validated('per_page', 15),
                search: $request->validated('search'),
                sortBy: $request->validated('sort_by', 'created_at'),
            );

            $paginator = $this->dispatcher->dispatch($query);

            $collection = UserResource::collection($paginator);

            return $this->successResponse($collection->response()->getData(true));
        }, trans('auth-core::messages.users_retrieved'), 200);
    }

    public function assignRole(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string',
            'guard_name' => 'string|max:255'
        ]);

        return $this->safeExecute(function () use ($request, $id) {
            $command = new AssignRoleToUserCommand(
                userId: $id,
                roleName: $request->input('role'),
                guardName: $request->input('guard_name', 'api')
            );

            $this->dispatcher->dispatch($command);

            return $this->successResponse(null, 'Role assigned successfully');
        }, 'Role assigned successfully', 200);
    }

    public function revokeRole(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string',
            'guard_name' => 'string|max:255'
        ]);

        return $this->safeExecute(function () use ($request, $id) {
            $command = new RevokeRoleFromUserCommand(
                userId: $id,
                roleName: $request->input('role'),
                guardName: $request->input('guard_name', 'api')
            );

            $this->dispatcher->dispatch($command);

            return $this->successResponse(null, 'Role revoked successfully');
        }, 'Role revoked successfully', 200);
    }
}
