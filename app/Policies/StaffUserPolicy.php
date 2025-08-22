<?php

namespace App\Policies;

use App\Models\StaffUser;
use App\Models\User;
use App\Enums\StaffRole;

class StaffUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user instanceof StaffUser;
    }

    public function view(User $user, StaffUser $staffUser): bool
    {
        if ($user instanceof StaffUser && $user->id === $staffUser->id) {
            return true;
        }

        return $user instanceof StaffUser && $user->hasRole(StaffRole::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user instanceof StaffUser && $user->hasRole(StaffRole::ADMIN->value);
    }

    public function update(User $user, StaffUser $staffUser): bool
    {
        if ($user instanceof StaffUser && $user->id === $staffUser->id) {
            return true;
        }

        return $user instanceof StaffUser && $user->hasRole(StaffRole::ADMIN->value);
    }

    public function delete(User $user, StaffUser $staffUser): bool
    {
        return $user instanceof StaffUser
            && $user->hasRole(StaffRole::ADMIN->value)
            && $user->id !== $staffUser->id;
    }
}
