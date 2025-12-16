<?php

namespace InnoSoft\AuthCore\Infrastructure\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InnoSoft\AuthCore\Domain\Shared\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
readonly class LaravelAuditLogger implements AuditLogger
{
    public function __construct(
        private Request $request
    ) {}

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
        $payload = [
            'type' => $type,
            'event' => $event,
            'user_id' => Auth::id() ?? $context['user_id'] ?? null,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'context' => $context
        ];

        Log::channel('daily')->log($level, $event, $payload);
    }
}