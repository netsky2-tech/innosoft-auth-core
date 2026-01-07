<?php

namespace InnoSoft\AuthCore\Application\Auth\Handlers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Domain\Auth\Exceptions\TwoFactorRequiredException;
use InnoSoft\AuthCore\Domain\Auth\Services\TokenIssuer;
use InnoSoft\AuthCore\Domain\Users\Exceptions\InvalidCredentialsException;
use InnoSoft\AuthCore\Domain\Users\Repositories\UserRepository;


final readonly class LoginUserHandler
{
    // Hash dummy precalculado (hash de "secret") para no gastar CPU generándolo al vuelo
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    public function __construct(
        private UserRepository $userRepository,
        private TokenIssuer    $tokenIssuer
    ) {}

    /**
     * @throws InvalidCredentialsException
     * @throws TwoFactorRequiredException
     */
    public function handle(LoginUserCommand $command): array
    {
        $user = $this->userRepository->findByEmail($command->email);

        // Always perform hash check to prevent timing analysis
        // If user is null, we hash a dummy string to simulate the same time cost
        $targetHash = $user ? $user->getPasswordHash() : self::DUMMY_HASH; // Dummy hash

        $validPassword = Hash::check($command->password, $targetHash);
        
        if (!$user || !$validPassword) {
            // 2. Missing Failed Event
            // Dispatch Failed event for auditing and rate limiting (e.g. Fail2Ban)
            Event::dispatch(new Failed(
                'sanctum',
                $user ? $this->userRepository->findAuthenticatableById($user->getId()) : null,
                ['email' => $command->email]
            ));
            
            throw new InvalidCredentialsException();
        }

        if($user->hasTwoFactorEnabled()){
            throw new TwoFactorRequiredException($user->getId());
        }

        // 3. Eloquent Leak Fix
        // We still need the Authenticatable for the Login event, but we should ensure
        // we are not leaking it out of this handler or using it for business logic.
        // The repository method findAuthenticatableById is acceptable here solely for framework integration.
        $eloquentUser = $this->userRepository->findAuthenticatableById($user->getId());
        if ($eloquentUser) {
            Event::dispatch(new Login(
                'sanctum',
                $eloquentUser,
                false
            ));
        }

        // Generate token
        $token = $this->tokenIssuer->issue($user, $command->deviceName);

        return [
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
            ]
        ];
    }
}