<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
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

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->isStaff()) {
            return $user->profileable->isAdvanced() || $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser() && $user->profileable->canManageUsers()) {
            if ($model->isBusinessUser()) {
                return $user->profileable->business_id === $model->profileable->business_id;
            }

            if ($model->isCustomer()) {
                return $model->profileable->businesses()
                    ->where('business_id', $user->profileable->business_id)
                    ->exists();
            }
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

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser() && $user->profileable->isAdmin()) {
            if ($model->isBusinessUser()) {
                return $user->profileable->business_id === $model->profileable->business_id;
            }

            if ($model->isCustomer()) {
                return $model->profileable->businesses()
                    ->where('business_id', $user->profileable->business_id)
                    ->exists();
            }
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isStaff() && $user->profileable->isAdmin()) {
            if ($model->isStaff() && $model->profileable->isAdmin()) {
                return false;
            }
            return true;
        }

        if ($user->isBusinessUser() && $user->profileable->isAdmin() && $model->isBusinessUser()) {
            return $user->profileable->business_id === $model->profileable->business_id;
        }

        return false;
    }
}
