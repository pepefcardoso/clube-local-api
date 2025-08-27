<?php

namespace App\Policies;

use App\Models\StaffUserProfile;
use App\Models\User;

class StaffUserProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->profileable->access_level === 'admin';
    }

    public function view(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return true;
        }

        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->profileable->access_level === 'admin';
    }

    public function update(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return true;
        }

        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        return false;
    }

    public function delete(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return false;
        }

        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            if ($staffUserProfile->access_level === 'admin') {
                return false;
            }
            return true;
        }

        return false;
    }

    public function restore(User $user, StaffUserProfile $staffUserProfile): bool
    {
        return $user->isStaff() && $user->profileable->access_level === 'admin';
    }

    public function forceDelete(User $user, StaffUserProfile $staffUserProfile): bool
    {
        return $user->isStaff() && $user->profileable->access_level === 'admin';
    }
}
