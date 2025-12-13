<?php

namespace InnoSoft\AuthCore\Domain\Shared\Services;

interface AuditLogger
{
    public function logSecurityEvent(string $event, array $context = []): void;
    public function logBusinessEvent(string $event, array $context = []): void;
}