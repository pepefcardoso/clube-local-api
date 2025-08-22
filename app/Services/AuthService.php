<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\BusinessUser;
use App\Models\StaffUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password, string $userType): array
    {
        $userModel = $this->getUserModel($userType);
        $user = $userModel::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (method_exists($user, 'is_active') && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        if ($user instanceof StaffUser && method_exists($user, 'updateLastLogin')) {
            $user->updateLastLogin();
        }

        $abilities = $this->getTokenAbilities($user);
        $token = $user->createToken(
            name: $userType . '_token',
            abilities: $abilities,
            expiresAt: now()->addDays(30)
        );

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $userType,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }

    public function logout($user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAll($user): void
    {
        $user->tokens()->delete();
    }

    public function refreshToken($user): array
    {
        $userType = $this->getUserType($user);
        $user->currentAccessToken()->delete();

        $abilities = $this->getTokenAbilities($user);
        $token = $user->createToken(
            name: $userType . '_token',
            abilities: $abilities,
            expiresAt: now()->addDays(30)
        );

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }

    public function getCurrentUser($user): array
    {
        $userType = $this->getUserType($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $userType,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'profile' => $user->only($user->getFillable()),
        ];
    }

    private function getUserModel(string $userType): string
    {
        return match ($userType) {
            'customer' => Customer::class,
            'business' => BusinessUser::class,
            'staff' => StaffUser::class,
            default => throw new \InvalidArgumentException('Invalid user type')
        };
    }

    private function getUserType($user): string
    {
        return match (true) {
            $user instanceof Customer => 'customer',
            $user instanceof BusinessUser => 'business',
            $user instanceof StaffUser => 'staff',
            default => 'unknown'
        };
    }

    private function getTokenAbilities($user): array
    {
        $abilities = [];

        if ($user instanceof Customer) {
            $abilities = ['customer:read', 'customer:update'];
            if ($user->isPremium()) {
                $abilities[] = 'customer:premium';
            }
        } elseif ($user instanceof BusinessUser) {
            $abilities = ['business:read', 'business:update'];
            if ($user->isManager()) {
                $abilities[] = 'business:manage';
            }
        } elseif ($user instanceof StaffUser) {
            $abilities = ['staff:read', 'staff:update'];
            if ($user->isAdmin()) {
                $abilities[] = 'staff:admin';
                $abilities[] = 'system:manage';
            }
        }

        return $abilities;
    }
}
