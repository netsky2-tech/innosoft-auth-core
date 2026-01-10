<?php

namespace InnoSoft\AuthCore\Application\Teams\Handlers;

use InnoSoft\AuthCore\Application\Teams\Commands\SwitchTeamCommand;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Teams\Services\TeamMembershipValidator;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;

final readonly class SwitchTeamHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenIssuer $tokenIssuer,
        private TeamMembershipValidator $teamValidator
    ) {}

    public function handle(SwitchTeamCommand $command): array
    {
        $user = $this->userRepository->findById($command->userId);
        
        // Validate if the user belongs to the team
        if (!$this->teamValidator->validate($user->getId(), $command->teamId)) {
            throw ValidationException::withMessages([
                'team_id' => [trans('auth-core::messages.user_not_in_team')]
            ]);
        }
        
        // Issue a new token with the team ID claim
        $token = $this->tokenIssuer->issue($user, $command->deviceName, ['current_team_id' => $command->teamId]);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'team_id' => $command->teamId
        ];
    }
}
