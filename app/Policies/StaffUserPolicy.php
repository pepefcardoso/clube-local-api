<?php

namespace App\Policies;

use App\Models\StaffUser;
use App\Models\User;

class StaffUserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, StaffUser $staffUser): bool
    {
        return $this->isSelfOrAdmin($user, $staffUser);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, StaffUser $staffUser): bool
    {
        return $this->isSelfOrAdmin($user, $staffUser);
    }

    public function delete(User $user, StaffUser $staffUser): bool
    {
        return $this->isAdmin($user) && $user->id !== $staffUser->id;
    }
}
