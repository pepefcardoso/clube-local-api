<?php

namespace App\Policies;

use App\Models\StaffUserProfile;
use App\Models\User;

class StaffUserProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function view(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return true;
        }

        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function update(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return true;
        }

        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function delete(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return false;
        }

        if (!($user->isStaff() && $user->profileable->isAdmin())) {
            return false;
        }

        if ($staffUserProfile->isAdmin()) {
            $adminCount = StaffUserProfile::where('access_level', 'admin')
                ->whereHas('user', fn($q) => $q->where('is_active', true))
                ->count();

            return $adminCount > 1;
        }

        return true;
    }

    public function promoteToAdmin(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id && !$user->profileable->isAdmin()) {
            return false;
        }

        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function demoteFromAdmin(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return false;
        }

        $adminCount = StaffUserProfile::where('access_level', 'admin')
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->count();

        if ($adminCount <= 1) {
            return false;
        }

        return $user->isStaff() && $user->profileable->isAdmin();
    }
}
