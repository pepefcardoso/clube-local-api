<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdvanced() || $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->canManageUsers();
        }

        return false;
    }

    public function view(User $user, Business $business): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdvanced() || $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->business_id === $business->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        return false;
    }

    public function update(User $user, Business $business): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser() && $user->profileable->isAdmin()) {
            return $user->profileable->business_id === $business->id;
        }

        return false;
    }

    public function delete(User $user, Business $business): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        return false;
    }

    public function restore(User $user, Business $business): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        return false;
    }

    public function forceDelete(User $user, Business $business): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        return false;
    }

    public function approve(User $user, Business $business): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        return false;
    }

    public function managePlans(User $user, Business $business): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        return false;
    }
}
