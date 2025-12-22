<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InnoSoft\AuthCore\Application\Users\Commands\CreateUserCommand;
use InnoSoft\AuthCore\Application\Users\Commands\DeleteUserCommand;
use InnoSoft\AuthCore\Application\Users\Commands\UpdateUserCommand;
use InnoSoft\AuthCore\Application\Users\Queries\GetUserQuery;
use InnoSoft\AuthCore\Application\Users\Queries\ListUsersQuery;
use InnoSoft\AuthCore\UI\Http\Requests\User\DeleteUserRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\ListUsersRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\CreateUserRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\UpdateUserRequest;
use InnoSoft\AuthCore\UI\Http\Resources\UserResource;
use InnoSoft\AuthCore\UI\Http\Responses\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ){
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['store']);
        $this->middleware('permission:users.update')->only(['update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $command = CreateUserCommand::fromRequest($request);

        // Dispatch the command
        $user = $this->dispatcher->dispatch($command);

        return $this->successResponse(new UserResource($user), 'User successfully created.', 201);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $command = UpdateUserCommand::fromRequest($id, $request);

        $user = $this->dispatcher->dispatch($command);

        return $this->successResponse(new UserResource($user), 'User successfully updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $command = new DeleteUserCommand(userId: $id);

        $this->dispatcher->dispatch($command);

        return $this->successResponse(null, 'User successfully deleted.', 204);
    }

    public function show(string $id): JsonResponse
    {
        $query = new GetUserQuery(userId: $id);

        $user = $this->dispatcher->dispatch($query);

        return $this->successResponse(new UserResource($user), 'User successfully retrieved.');
    }

    public function index(ListUsersRequest $request): JsonResponse
    {
        $query = new ListUsersQuery(
            page: $request->validated('page', 1),
            perPage: $request->validated('per_page', 15),
            search: $request->validated('search'),
            sortBy: $request->validated('sort_by', 'created_at'),
        );

        $paginator = $this->dispatcher->dispatch($query);

        $collection = UserResource::collection($paginator);

        return $this->successResponse($collection->response()->getData(true), 'Users retrieved.');
    }
}