<?php

namespace App\Policies;

use App\Models\BusinessUser;
use App\Models\User;
use App\Enums\BusinessUserRole;

class BusinessUserPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user instanceof \App\Models\StaffUser) {
            return true;
        }

        return $user instanceof BusinessUser && $user->hasRole(BusinessUserRole::MANAGER->value);
    }

    public function view(User $user, BusinessUser $businessUser): bool
    {
        if ($user instanceof BusinessUser && $user->id === $businessUser->id) {
            return true;
        }

        if ($user instanceof \App\Models\StaffUser) {
            return true;
        }

        return $user instanceof BusinessUser
            && $user->hasRole(BusinessUserRole::MANAGER->value);
    }

    public function create(User $user): bool
    {
        if ($user instanceof \App\Models\StaffUser) {
            return true;
        }

        return $user instanceof BusinessUser && $user->hasRole(BusinessUserRole::MANAGER->value);
    }

    public function update(User $user, BusinessUser $businessUser): bool
    {
        if ($user instanceof BusinessUser && $user->id === $businessUser->id) {
            return true;
        }

        if ($user instanceof \App\Models\StaffUser) {
            return true;
        }

        return $user instanceof BusinessUser
            && $user->hasRole(BusinessUserRole::MANAGER->value);
    }

    public function delete(User $user, BusinessUser $businessUser): bool
    {
        if ($user instanceof \App\Models\StaffUser) {
            return true;
        }

        return $user instanceof BusinessUser
            && $user->hasRole(BusinessUserRole::MANAGER->value)
            && $user->id !== $businessUser->id;
    }
}
