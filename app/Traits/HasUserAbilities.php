<?php

namespace App\Traits;

trait HasUserAbilities
{
    public function getUserAbilities(): array
    {
        $abilities = [];

        $abilities[] = 'profile:read';
        $abilities[] = 'profile:update';

        if ($this->isCustomer()) {
            $abilities = array_merge($abilities, $this->getCustomerAbilities());
        }

        if ($this->isStaff()) {
            $abilities = array_merge($abilities, $this->getStaffAbilities());
        }

        if ($this->isBusinessUser()) {
            $abilities = array_merge($abilities, $this->getBusinessUserAbilities());
        }

        return array_unique($abilities);
    }

    private function getCustomerAbilities(): array
    {
        return [
            'customer:profile:read',
            'customer:profile:update',
            'orders:create',
            'orders:read',
        ];
    }

    private function getStaffAbilities(): array
    {
        $abilities = [
            'staff:dashboard:read',
            'staff:reports:read',
        ];

        $staffProfile = $this->profileable;

        if (!$staffProfile) {
            return $abilities;
        }

        switch ($staffProfile->access_level) {
            case 'admin':
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
                break;

            case 'advanced':
                $abilities = array_merge($abilities, [
                    'staff:users:read',
                    'staff:reports:advanced',
                ]);
                break;
        }

        if (is_array($staffProfile->system_permissions)) {
            $abilities = array_merge($abilities, $staffProfile->system_permissions);
        }

        return $abilities;
    }

    private function getBusinessUserAbilities(): array
    {
        $abilities = [];

        $businessProfiles = $this->businessUserProfiles()->active()->get();

        foreach ($businessProfiles as $profile) {
            $businessId = $profile->business_id;

            $abilities[] = "business:{$businessId}:read";
            $abilities[] = "business:{$businessId}:orders:read";

            if ($this->hasBusinessAdminPermission($businessId)) {
                $abilities = array_merge($abilities, [
                    "business:{$businessId}:manage",
                    "business:{$businessId}:users:manage",
                    "business:{$businessId}:settings:update",
                ]);
            }

            if (is_array($profile->permissions)) {
                foreach ($profile->permissions as $permission) {
                    $abilities[] = "business:{$businessId}:{$permission}";
                }
            }
        }

        return $abilities;
    }

    public function generateApiToken(): string
    {
        $this->tokens()->delete();

        $abilities = $this->getUserAbilities();

        return $this->createToken('auth-token', $abilities)->plainTextToken;
    }
}
