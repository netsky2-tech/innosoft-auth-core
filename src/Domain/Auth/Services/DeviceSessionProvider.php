<?php

namespace InnoSoft\AuthCore\Domain\Auth\Services;

interface DeviceSessionProvider
{
    /**
     * Returns a list of active sessions/devices for the user.
     * Should return array of DTOs or associative arrays.
     */
    public function getSessions(string $userId, ?string $currentTokenId = null): array;

    /**
     * Revokes a specific session/token.
     */
    public function revokeSession(string $userId, string $sessionId): void;

    /**
     * Revokes all sessions except the current one.
     */
    public function revokeOthers(string $userId, string $currentTokenId): void;
}
