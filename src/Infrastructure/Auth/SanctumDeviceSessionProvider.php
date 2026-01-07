<?php

namespace InnoSoft\AuthCore\Infrastructure\Auth;

use InnoSoft\AuthCore\Domain\Auth\Services\DeviceSessionProvider;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\User as EloquentUser;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumDeviceSessionProvider implements DeviceSessionProvider
{
    public function getSessions(string $userId, ?string $currentTokenId = null): array
    {
        // We use the Eloquent model to access the 'tokens' relationship provided by Sanctum
        $user = EloquentUser::find($userId);

        if (!$user) {
            return [];
        }

        return $user->tokens->map(function ($token) use ($currentTokenId) {
            return [
                'id' => (string) $token->id,
                'device_name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at->toIso8601String(),
                'is_current' => $currentTokenId && (string)$token->id === $currentTokenId,
                // You could add IP or User Agent if you customized the token table
            ];
        })->toArray();
    }

    public function revokeSession(string $userId, string $sessionId): void
    {
        $token = PersonalAccessToken::find($sessionId);

        // Security check: Ensure the token belongs to the user requesting revocation
        if ($token && (string)$token->tokenable_id === $userId) {
            $token->delete();
        }
    }

    public function revokeOthers(string $userId, string $currentTokenId): void
    {
        $user = EloquentUser::find($userId);

        if ($user) {
            $user->tokens()
                ->where('id', '!=', $currentTokenId)
                ->delete();
        }
    }
}
