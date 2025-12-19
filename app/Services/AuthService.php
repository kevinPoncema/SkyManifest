<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private AuthRepository $authRepository
    ) {}

    /**
     * Register a new user and return access token.
     */
    public function register(array $data): array
    {
        $user = $this->authRepository->create($data);

        $token = $user->createToken('SkyManifest API Token', ['*'], now()->addDays(30))->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30)->toISOString(),
        ];
    }

    /**
     * Authenticate user and return access token.
     */
    public function login(array $credentials): array
    {
        $user = $this->authRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Revoke existing tokens (optional - for single session)
        $this->authRepository->revokeAllTokens($user);

        // Create new token
        $token = $user->createToken(
            'SkyManifest API Token',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30)->toISOString(),
        ];
    }

    /**
     * Logout user by revoking current token.
     */
    public function logout(User $user, ?string $currentTokenId = null): void
    {
        if ($currentTokenId) {
            // Revoke only current token
            $user->tokens()->where('id', $currentTokenId)->delete();
        } else {
            // Revoke all tokens
            $this->authRepository->revokeAllTokens($user);
        }
    }

    /**
     * Refresh user token.
     */
    public function refreshToken(User $user): array
    {
        // Revoke current tokens
        $this->authRepository->revokeAllTokens($user);

        // Create new token
        $token = $user->createToken(
            'SkyManifest API Token',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30)->toISOString(),
        ];
    }

    /**
     * Get user profile.
     */
    public function getProfile(User $user): User
    {
        return $user->load(['projects.domains', 'projects.deploys']);
    }
}
