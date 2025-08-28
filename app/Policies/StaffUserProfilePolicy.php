<?php

namespace App\Policies;

use App\Models\StaffUserProfile;
use App\Models\User;

class StaffUserProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaffAdmin($user);
    }

    public function view(User $user, StaffUserProfile $staffUserProfile): bool
    {
        return ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) ||
            $this->isStaffAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaffAdmin($user);
    }

    public function update(User $user, StaffUserProfile $staffUserProfile): bool
    {
        return ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) ||
            $this->isStaffAdmin($user);
    }

    public function delete(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return false;
        }

        if (!$this->isStaffAdmin($user)) {
            return false;
        }

        if ($staffUserProfile->access_level === 'admin') {
            return $this->canManageAdmin($user);
        }

        return true;
    }

    public function createAdmin(User $user): bool
    {
        return $this->canManageAdmin($user);
    }

    public function promoteToAdmin(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if (
            $staffUserProfile->user && $user->id === $staffUserProfile->user->id &&
            $user->profileable->access_level !== 'admin'
        ) {
            return false;
        }

        return $this->canManageAdmin($user);
    }

    public function demoteFromAdmin(User $user, StaffUserProfile $staffUserProfile): bool
    {
        if ($staffUserProfile->user && $user->id === $staffUserProfile->user->id) {
            return false;
        }

        if ($this->isLastAdmin($staffUserProfile)) {
            return false;
        }

        return $this->canManageAdmin($user);
    }

    private function isStaffAdmin(User $user): bool
    {
        return $user->isStaff() && $user->profileable->access_level === 'admin';
    }

    private function canManageAdmin(User $user): bool
    {
        return $this->isStaffAdmin($user);
    }

    private function isLastAdmin(StaffUserProfile $staffUserProfile): bool
    {
        return $staffUserProfile->access_level === 'admin' &&
            StaffUserProfile::where('access_level', 'admin')
                ->whereHas('user', fn($q) => $q->where('is_active', true))
                ->count() <= 1;
    }
}
