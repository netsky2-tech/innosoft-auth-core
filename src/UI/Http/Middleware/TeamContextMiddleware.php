<?php

namespace InnoSoft\AuthCore\UI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class TeamContextMiddleware
{
    public function __construct(
        protected TeamMembershipValidator $teamValidator
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Si la feature está apagada, pasamos
        if (!config('auth-core.features.teams', false)) {
            return $next($request);
        }

        $user = $request->user();

        // 2. Si no hay usuario autenticado, no podemos validar equipos
        if (!$user) {
            return $next($request);
        }

        // 3. Intentar obtener el Team ID del Header
        $teamId = $request->header('X-Team-ID');

        if ($teamId) {
            
            // Convertir getAuthIdentifier() a string para cumplir con la interfaz
            if ($this->teamValidator->validate((string) $user->getAuthIdentifier(), $teamId)) {
                // 5. Seteamos el contexto global de Spatie
                app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
            } else {
                // Si intenta acceder a un equipo que no es suyo -> 403
                abort(403, trans('auth-core::messages.user_not_in_team'));
            }
        }

        return $next($request);
    }
}
