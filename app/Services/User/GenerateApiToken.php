<?php

namespace App\Services\User;

use App\Models\User;

class GenerateApiToken
{
    public function generate(User $user): string
    {
        $user->tokens()->delete();

        $abilities = $this->getUserAbilities($user);

        return $user->createToken('auth-token', $abilities)->plainTextToken;
    }

    private function getUserAbilities(User $user): array
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
            $staffProfile = $user->profileable;

            if ($staffProfile && $staffProfile->access_level === 'admin') {
                $abilities = array_merge($abilities, [
                    'admin:users:read',
                    'admin:users:create',
                    'admin:users:update',
                    'admin:users:delete',
                    'admin:staff:create',
                    'admin:staff:update',
                    'admin:staff:delete',
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

        if ($user->isBusinessUser()) {
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
        }

        return array_unique($abilities);
    }
}
