<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InnoSoft\AuthCore\Application\Teams\Commands\SwitchTeamCommand;
use InnoSoft\AuthCore\Application\Teams\Queries\ListUserTeamsQuery;
use InnoSoft\AuthCore\UI\Http\Responses\ApiResponse;
use InnoSoft\AuthCore\UI\Http\Traits\HandlesApiExecution;
use Illuminate\Contracts\Bus\Dispatcher;

class TeamController extends Controller
{
    use HandlesApiExecution, ApiResponse;

    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    /**
     * List all teams the user belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            $query = new ListUserTeamsQuery($request->user()->id);
            return $this->dispatcher->dispatch($query);
        }, trans('auth-core::messages.teams_retrieved_successfully'), 200);
    }

    /**
     * Switch the current team context.
     * This might return a new token or just confirm the switch depending on implementation.
     */
    public function switch(Request $request, string $id): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $id) {
            $command = new SwitchTeamCommand(
                userId: $request->user()->id,
                teamId: $id,
                deviceName: $request->user()->currentAccessToken()->name ?? 'unknown'
            );

            return $this->dispatcher->dispatch($command);
        }, trans('auth-core::messages.team_switched_successfully'), 200);
    }
}
