<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login
{
    public function login(array $credentials): array
    {
        $user = User::active()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $user->updateLastLogin();

        $token = $user->generateApiToken();

        return [
            'user' => $user->load(['customerProfile', 'businessUserProfiles', 'staffUserProfile']),
            'token' => $token,
            'abilities' => $this->getUserAbilities($user),
        ];
    }

    public function getUserAbilities(User $user): array
    {
        $abilities = [];

        $abilities[] = 'profile:read';
        $abilities[] = 'profile:update';

        if ($user->isCustomer()) {
            $abilities = array_merge($abilities, [
                'customer:profile:read',
                'customer:profile:update',
                'orders:create',
                'orders:read',
            ]);
        }

        if ($user->isStaff()) {
            $staffProfile = $user->staffUserProfile;

            if ($staffProfile->isAdmin()) {
                $abilities = array_merge($abilities, [
                    'admin:users:read',
                    'admin:users:create',
                    'admin:users:update',
                    'admin:users:delete',
                    'admin:businesses:read',
                    'admin:businesses:approve',
                    'admin:system:manage',
                ]);
            } else {
                $abilities = array_merge($abilities, [
                    'staff:dashboard:read',
                    'staff:reports:read',
                ]);
            }
        }

        $businessProfiles = $user->businessUserProfiles()->active()->get();
        foreach ($businessProfiles as $profile) {
            $businessId = $profile->business_id;

            if ($user->hasRole('business_admin')) {
                $abilities = array_merge($abilities, [
                    "business:{$businessId}:manage",
                    "business:{$businessId}:users:manage",
                    "business:{$businessId}:settings:update",
                ]);
            } else {
                $abilities = array_merge($abilities, [
                    "business:{$businessId}:read",
                    "business:{$businessId}:orders:read",
                ]);
            }
        }

        return array_unique($abilities);
    }
}
