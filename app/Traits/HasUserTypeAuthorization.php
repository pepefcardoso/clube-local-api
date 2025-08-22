<?php

namespace App\Traits;

use App\Enums\UserType;

trait HasUserTypeAuthorization
{
    public function getUserType(): UserType
    {
        return match (static::class) {
            \App\Models\Customer::class => UserType::CUSTOMER,
            \App\Models\BusinessUser::class => UserType::BUSINESS_USER,
            \App\Models\StaffUser::class => UserType::STAFF_USER,
            default => throw new \InvalidArgumentException('Unknown user type'),
        };
    }

    public function canManage(string $resource): bool
    {
        return match ($this->getUserType()) {
            UserType::STAFF_USER => $this->hasRole('staff.admin'),
            UserType::BUSINESS_USER => $this->hasRole('business.manager') && $resource === 'business_users',
            default => false,
        };
    }

    public function getTokenAbilities(): array
    {
        $roles = $this->getRoleNames();
        $abilities = [];

        foreach ($roles as $role) {
            $enumRole = match ($this->getUserType()) {
                UserType::CUSTOMER => \App\Enums\CustomerRole::tryFrom($role),
                UserType::BUSINESS_USER => \App\Enums\BusinessUserRole::tryFrom($role),
                UserType::STAFF_USER => \App\Enums\StaffRole::tryFrom($role),
            };

            if ($enumRole) {
                $abilities = array_merge($abilities, $enumRole->getPermissions());
            }
        }

        return array_unique($abilities);
    }
}
