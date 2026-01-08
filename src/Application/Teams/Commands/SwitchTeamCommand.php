<?php

namespace InnoSoft\AuthCore\Application\Teams\Commands;

final readonly class SwitchTeamCommand
{
    public function __construct(
        public string $userId,
        public string $teamId,
        public string $deviceName
    ) {}
}
