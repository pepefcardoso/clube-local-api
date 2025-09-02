<?php

namespace App\Policies;

use App\Models\PlatformPlan;
use App\Models\User;

class PlatformPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function view(User $user, PlatformPlan $platformPlan): bool
    {
        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function update(User $user, PlatformPlan $platformPlan): bool
    {
        return $user->isStaff() && $user->profileable->isAdmin();
    }

    public function delete(User $user, PlatformPlan $platformPlan): bool
    {
        if ($platformPlan->businesses()->exists()) {
            return false;
        }

        return $user->isStaff() && $user->profileable->isAdmin();
    }
}
