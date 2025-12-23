<?php

namespace InnoSoft\AuthCore\Infrastructure\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use Illuminate\Support\Facades\Log;

readonly class LaravelAuditLogger implements AuditLogger
{

    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->writeLog('SECURITY_EVENT', $event, $context, 'warning');
    }

    public function logBusinessEvent(string $event, array $context = []): void
    {
        $this->writeLog('BUSINESS_EVENT', $event, $context, 'info');
    }

    private function writeLog(string $type, string $event, array $context, string $level): void
    {
        // Detectamos el contexto de ejecución
        $isRunningInConsole = app()->runningInConsole();

        $payload = [
            'type' => $type,
            'event' => $event,
            'user_id'    => $context['user_id'] ?? Auth::id() ?? ($isRunningInConsole ? 'SYSTEM' : null),
            'email' => $context['email'] ?? null,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? ($isRunningInConsole ? 'CLI/Job' : 'Unknown'),
            'timestamp' => now()->toIso8601String(),
            'context' => $context
        ];

        Log::channel('daily')->log($level, $event, $payload);
    }

    public function log(string $action, string $description, array $context = []): void
    {
        // Iniciamos el builder de Spatie
        $activity = activity();

        // 1. Definir el "Causante" (quien hizo la acción)
        // Intentamos sacar el usuario del contexto, o del Auth facade actual
        $user = $context['user'] ?? Auth::user();

        if ($user) {
            $activity->causedBy($user);
            unset($context['user']); // Limpiamos para no duplicar en properties
        }

        // 2. Definir el "Sujeto" (sobre qué se actuó), si aplica
        if (isset($context['model'])) {
            $activity->performedOn($context['model']);
            unset($context['model']);
        }

        // 3. Registrar propiedades extra (IP, Browser, metadatos)
        $properties = array_merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source'     => app()->runningInConsole() ? 'CLI' : 'HTTP'
        ], $context);

        // 4. Escribir el log
        $activity
            ->withProperties($properties)
            ->event($action) // Ej: "auth.login", "security.risk"
            ->log($description); // Ej: "User logged in successfully"
    }
}