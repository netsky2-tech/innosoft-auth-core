<?php

namespace InnoSoft\AuthCore\Application\Teams\Queries\Handlers;

use InnoSoft\AuthCore\Application\Teams\Queries\ListUserTeamsQuery;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;

final readonly class ListUserTeamsHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function handle(ListUserTeamsQuery $query): array
    {
        // Since Team persistence is external, we rely on the User model having a relationship or method
        // to retrieve teams. We assume the host application implements a 'teams' relationship on the User model.
        
        $user = $this->userRepository->findAuthenticatableById($query->userId);
        
        if (method_exists($user, 'teams')) {
            // Check if it's a relationship (returns Collection) or method
            $teams = $user->teams;
            
            // If it's a relationship instance (e.g. HasMany), get the results
            if ($teams instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                $teams = $teams->get();
            }
            
            return $teams->toArray();
        }
        
        return [];
    }
}
