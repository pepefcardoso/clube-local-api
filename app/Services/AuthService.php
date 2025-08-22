<?php

namespace App\Services;

use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, RateLimiter};
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\PermissionRegistrar;

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

        if (isset($user->is_active) && $user->is_active == false) {
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->createTokenForUser($user, $userType);
    }

    public function logout($user): void
    {
        $this->deleteCurrentUserToken($user);
    }

    public function logoutAll($user): void
    {
        // Delete all tokens for this user
        $user->tokens()->delete();
    }

    public function refresh($user): array
    {
        $userType = $user->getUserType();

        // Delete the current token
        $this->deleteCurrentUserToken($user);

        // Create a new token
        return $this->createTokenForUser($user, $userType);
    }

    public function me($user): array
    {
        return $this->formatUserData($user);
    }

    private function deleteCurrentUserToken($user): void
    {
        $request = app(Request::class);
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return;
        }

        // Parse the token
        $tokenParts = explode('|', $bearerToken);
        if (count($tokenParts) !== 2) {
            return;
        }

        [$id, $token] = $tokenParts;
        $hashedToken = hash('sha256', $token);

        // Find and delete the token
        PersonalAccessToken::where('id', $id)
            ->where('token', $hashedToken)
            ->where('tokenable_type', get_class($user))
            ->where('tokenable_id', $user->id)
            ->delete();
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
