<?php

namespace App\Policies;

use App\Models\User;

abstract class BasePolicy
{
    protected function isStaff(User $user): bool
    {
        return $user instanceof \App\Models\StaffUser;
    }

    protected function isAdmin(User $user): bool
    {
        return $this->isStaff($user) && $user->hasRole('staff.admin');
    }

    protected function isSelfOrAdmin(User $user, $model): bool
    {
        return $user->id === $model->id || $this->isAdmin($user);
    }
}
