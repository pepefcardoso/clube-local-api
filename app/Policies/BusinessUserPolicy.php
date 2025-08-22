<?php

namespace App\Policies;

use App\Models\BusinessUser;
use App\Models\User;
use App\Enums\BusinessUserRole;

class BusinessUserPolicy extends BasePolicy
{
    protected function isBusinessManager(User $user): bool
    {
        return $user instanceof BusinessUser && $user->hasRole(BusinessUserRole::MANAGER->value);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaff($user) || $this->isBusinessManager($user);
    }

    public function view(User $user, BusinessUser $businessUser): bool
    {
        if ($user instanceof BusinessUser && $user->id === $businessUser->id) {
            return true;
        }

        return $this->isStaff($user) || $this->isBusinessManager($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user) || $this->isBusinessManager($user);
    }

    public function update(User $user, BusinessUser $businessUser): bool
    {
        if ($user instanceof BusinessUser && $user->id === $businessUser->id) {
            return true;
        }

        return $this->isStaff($user) || $this->isBusinessManager($user);
    }

    public function delete(User $user, BusinessUser $businessUser): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        return $this->isBusinessManager($user) && $user->id !== $businessUser->id;
    }
}
