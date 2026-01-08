<?php

namespace InnoSoft\AuthCore\UI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class TeamContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo actuamos si la feature está habilitada en la configuración
        if (config('auth-core.features.teams', false)) {
            $teamId = $request->header('X-Team-ID');

            // Si el request trae contexto, se lo inyectamos a la librería de permisos
            if ($teamId) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
            }
        }

        return $next($request);
    }
}