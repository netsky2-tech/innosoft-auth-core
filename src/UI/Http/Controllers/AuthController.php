<?php

namespace InnoSoft\AuthCore\UI\Http\Controllers;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InnoSoft\AuthCore\Application\Auth\Commands\ConfirmTwoFactorCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\DisableTwoFactorCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\EnableTwoFactorCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\LoginUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\LogoutUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RegisterUserCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RequestPasswordResetCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\ResetPasswordCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RevokeOtherSessionsCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\RevokeUserSessionCommand;
use InnoSoft\AuthCore\Application\Auth\Commands\VerifyTwoFactorLoginCommand;
use InnoSoft\AuthCore\Application\Auth\Queries\ListUserSessionsQuery;
use InnoSoft\AuthCore\Domain\Auth\Exceptions\TwoFactorRequiredException;
use InnoSoft\AuthCore\Domain\Auth\Services\TwoFactorChallengeService;
use InnoSoft\AuthCore\UI\Http\Requests\ConfirmTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\DisableTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\EnableTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ForgotPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\LoginRequest;
use InnoSoft\AuthCore\UI\Http\Requests\ResetPasswordRequest;
use InnoSoft\AuthCore\UI\Http\Requests\User\CreateUserRequest;
use InnoSoft\AuthCore\UI\Http\Requests\VerifyTwoFactorRequest;
use InnoSoft\AuthCore\UI\Http\Responses\ApiResponse;
use InnoSoft\AuthCore\UI\Http\Traits\HandlesApiExecution;

class AuthController extends Controller
{
    use HandlesApiExecution, ApiResponse;

    public function __construct(
        private readonly TwoFactorChallengeService $challengeService,
        private readonly Dispatcher                $dispatcher,
    )
    {
    }

    /**
     * Handles new user registration.
     * Dispatches command to create user entity and hash password.
     */
    public function register(CreateUserRequest $request): JsonResponse
    {
        if (!config('auth-core.features.registration', false)) {
            return $this->errorResponse(trans('auth-core::messages.registration_disabled'), 403);
        }

        return $this->safeExecute(function () use ($request) {

            $command = new RegisterUserCommand(
                name: $request->validated('name'),
                email: $request->validated('email'),
                password: $request->validated('password')
            );

            $this->dispatcher->dispatch($command);

        }, trans('auth-core::messages.user_registered_successfully'), 201);

    }

    /**
     * Authenticates a user.
     * If 2FA is enabled for the user, intercepts the flow and returns a challenge token
     * instead of an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            try {
                $command = new LoginUserCommand(
                    email: $request->validated('email'),
                    password: $request->validated('password'),
                    deviceName: $request->device_name ?? 'unknown'
                );

                return $this->dispatcher->dispatch($command);

            } catch (TwoFactorRequiredException $e) {
                // Intercept login to enforce 2FA flow
                $challengeToken = $this->challengeService->createChallenge($e->userId);

                return $this->twoFactorRequiredResponse($challengeToken, 300);
            }
        }, trans('auth-core::messages.logged_in_successfully'), 200);
    }

    /**
     * Logs out the current user by revoking the access token used for the request.
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            $command = new LogoutUserCommand(
                userId: $request->user()->id,
                sessionId: $request->user()->currentAccessToken()->id
            );

            $this->dispatcher->dispatch($command);
        }, trans('auth-core::messages.logged_out_successfully'), 200);
    }

    /**
     * Initiates the password reset process by sending an email with a reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {

            $command = new RequestPasswordResetCommand($request->validated('email'));

            $this->dispatcher->dispatch($command);

        }, trans('auth-core::messages.password_reset_link_sent'), 200);
    }

    /**
     * Completes the password reset process using the token from the email.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {

            $command = new ResetPasswordCommand(
                $request->validated('email'),
                $request->validated('token'),
                $request->validated('password')
            );

            return $this->dispatcher->dispatch($command);

        }, trans('auth-core::messages.password_reset_successfully'), 200);

    }

    /**
     * Completes the login process for users with 2FA enabled.
     * Exchanges a valid challenge token and TOTP code for an access token.
     */
    public function verifyTwoFactor(VerifyTwoFactorRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {

            $command = new VerifyTwoFactorLoginCommand(
                challengeToken: $request->validated('challenge_token'),
                code: $request->validated('code'),
                deviceName: $request->validated('device_name')
            );

            return $this->dispatcher->dispatch($command);


        }, trans('auth-core::messages.two_factor_verified'), 200);
    }

    /**
     * Starts the 2FA enrollment process.
     * Generates a secret key and QR code data, but does not activate 2FA yet.
     */
    public function enableTwoFactor(EnableTwoFactorRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {

            $command = new EnableTwoFactorCommand($request->user()->id);

            return $this->dispatcher->dispatch($command);

        }, trans('auth-core::messages.two_factor_enabled'), 200);
    }

    /**
     * Finalizes 2FA enrollment.
     * Verifies the first code generated by the user's app and activates 2FA on the account.
     */
    public function confirmTwoFactor(ConfirmTwoFactorRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {

            $command = new ConfirmTwoFactorCommand(
                userId: $request->user()->id,
                code: $request->validated('code')
            );

            return $this->dispatcher->dispatch($command);

        }, trans('auth-core::messages.two_factor_verified'), 200);
    }

    /**
     * Disables 2FA for the user.
     * Requires current password verification to prevent unauthorized removal.
     */
    public function disableTwoFactor(DisableTwoFactorRequest $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {

            $command = new DisableTwoFactorCommand(
                userId: $request->user()->id,
                currentPassword: $request->validated('current_password')
            );

            return $this->dispatcher->dispatch($command);

        }, trans('auth-core::messages.two_factor_disabled'), 200);
    }

    /**
     * Lists all active sessions (devices) for the current user.
     */
    public function getSessions(Request $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            $query = new ListUserSessionsQuery(
                userId: $request->user()->id,
                currentTokenId: $request->user()->currentAccessToken()->id
            );

            return $this->dispatcher->dispatch($query);
        }, trans('auth-core::messages.active_sessions_retrieved'), 200);
    }

    /**
     * Revokes a specific session by its ID.
     */
    public function revokeSession(Request $request, string $sessionId): JsonResponse
    {
        return $this->safeExecute(function () use ($request, $sessionId) {
            $command = new RevokeUserSessionCommand(
                userId: $request->user()->id,
                sessionId: $sessionId
            );

            $this->dispatcher->dispatch($command);
        }, trans('auth-core::messages.session_revoked_successfully'), 200);
    }

    /**
     * Revokes all sessions except the current one.
     */
    public function revokeOtherSessions(Request $request): JsonResponse
    {
        return $this->safeExecute(function () use ($request) {
            $command = new RevokeOtherSessionsCommand(
                userId: $request->user()->id,
                currentTokenId: $request->user()->currentAccessToken()->id
            );

            $this->dispatcher->dispatch($command);
        }, trans('auth-core::messages.other_sessions_revoked'), 200);
    }
}
