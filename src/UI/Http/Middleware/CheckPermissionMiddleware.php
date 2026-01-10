<?php

namespace InnoSoft\AuthCore\UI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => trans('auth-core::messages.unauthenticated')], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->can($permission)) {
            return response()->json([
                'message' => trans('auth-core::messages.unauthorized_missing_permission', ['permission' => $permission])
            ], 403);
        }

        return $next($request);
    }
}