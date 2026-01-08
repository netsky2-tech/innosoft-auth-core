<?php

namespace InnoSoft\AuthCore\Infrastructure\Teams;

use InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator;
use InnoSoft\AuthCore\Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;

class HostTeamMembershipValidator implements TeamMembershipValidator
{
    public function validate(string $userId, string $teamId): bool
    {
        // Retrieve the Eloquent user model
        $eloquentUser = EloquentUser::find($userId);

        if (!$eloquentUser) {
            return false;
        }

        // Check if the host application has defined a way to check team membership.
        // We look for a method 'belongsToTeam' or a relationship 'teams'.
        
        if (method_exists($eloquentUser, 'belongsToTeam')) {
            return $eloquentUser->belongsToTeam($teamId);
        }

        if (method_exists($eloquentUser, 'teams')) {
            // Assuming 'teams' is a relationship that returns a collection of teams
            // and each team has an 'id' attribute.
            return $eloquentUser->teams->contains('id', $teamId);
        }

        // If no method is found, we default to true (open) or false (strict).
        // Given the requirement to "solidify" and not assume, defaulting to false (strict) is safer,
        // forcing the host to implement one of these methods if they want to use this feature.
        // However, to avoid breaking existing implementations that might not have teams yet,
        // we should check if the feature is enabled. But this validator is likely called only when switching teams.
        
        return false;
    }
}
