<?php

namespace App\Services;

use App\Enums\UserType;
use Illuminate\Support\Facades\{Hash, RateLimiter};
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password, string $userType, bool $remember = false): array
    {
        $key = $email . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again later.'],
            ]);
        }

        $userTypeEnum = UserType::tryFrom($userType);
        if (!$userTypeEnum) {
            throw ValidationException::withMessages([
                'user_type' => ['Invalid user type'],
            ]);
        }

        $userModel = $userTypeEnum->getModelClass();
        $user = $userModel::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (method_exists($user, 'is_active') && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        RateLimiter::clear($key);

        $abilities = $user->getTokenAbilities();
        $expiresAt = $remember ? now()->addDays(30) : now()->addDay();

        $token = $user->createToken(
            name: $userType . '_token',
            abilities: $abilities,
            expiresAt: $expiresAt
        );

        return [
            'user' => $this->formatUserData($user),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'abilities' => $abilities,
        ];
    }

    public function register(array $data, UserType $userType): array
    {
        $data['password'] = Hash::make($data['password']);

        $userModel = $userType->getModelClass();
        $user = $userModel::create($data);

        $user->assignRole($userType->getDefaultRole());

        return $this->createTokenForUser($user, $userType);
    }

    public function logout($user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function logoutAll($user): void
    {
        $user->tokens()->delete();
    }

    public function refresh($user): array
    {
        $oldToken = $user->currentAccessToken();
        $userType = $user->getUserType();

        $oldToken->delete();

        return $this->createTokenForUser($user, $userType);
    }

    public function me($user): array
    {
        return $this->formatUserData($user);
    }

    private function createTokenForUser($user, UserType $userType): array
    {
        $abilities = $user->getTokenAbilities();

        $token = $user->createToken(
            name: $userType->value . '_token',
            abilities: $abilities,
            expiresAt: now()->addDay()
        );

        return [
            'user' => $this->formatUserData($user),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'abilities' => $abilities,
        ];
    }

    private function formatUserData($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $user->getUserType()->value,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'abilities' => $user->getTokenAbilities(),
        ];
    }
}
