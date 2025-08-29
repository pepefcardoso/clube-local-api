<?php

namespace App\Policies;

use App\Models\BusinessUserProfile;
use App\Models\User;

class BusinessUserProfilePolicy
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

    public function view(User $user, BusinessUserProfile $businessUserProfile): bool
    {
        if ($businessUserProfile->user && $user->id === $businessUserProfile->user->id) {
            return true;
        }

        if ($user->isStaff()) {
            return $user->profileable->isAdvanced() || $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser() && $user->profileable->canManageUsers()) {
            return $user->profileable->business_id === $businessUserProfile->business_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->isAdmin();
        }

        return false;
    }

    public function update(User $user, BusinessUserProfile $businessUserProfile): bool
    {
        if ($businessUserProfile->user && $user->id === $businessUserProfile->user->id) {
            return true;
        }

        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser() && $user->profileable->isAdmin()) {
            return $user->profileable->business_id === $businessUserProfile->business_id;
        }

        return false;
    }

    public function delete(User $user, BusinessUserProfile $businessUserProfile): bool
    {
        if ($businessUserProfile->user && $user->id === $businessUserProfile->user->id) {
            return false;
        }

        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser() && $user->profileable->isAdmin()) {
            return $user->profileable->business_id === $businessUserProfile->business_id;
        }

        return false;
    }
}
