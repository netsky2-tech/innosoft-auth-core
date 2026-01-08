<?php

namespace InnoSoft\AuthCore\Domain\Teams\Services;

interface TeamMembershipValidator
{
    /**
     * Validate if the user belongs to the given team.
     *
     * @param string $userId
     * @param string $teamId
     * @return bool
     */
    public function validate(string $userId, string $teamId): bool;
}
