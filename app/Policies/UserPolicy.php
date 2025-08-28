<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        if ($user->isBusinessUser()) {
            return true;
        }

        return false;
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        if ($user->isBusinessUser() && $model->isBusinessUser()) {
            $userBusinessId = $user->profileable->business_id;
            $modelBusinessId = $model->profileable->business_id;

            return $userBusinessId === $modelBusinessId;
        }

        if ($user->isBusinessUser() && $model->isCustomer()) {
            $userBusinessId = $user->profileable->business_id;

            return $model->profileable->businesses()
                ->where('business_id', $userBusinessId)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        if ($user->isBusinessUser() && $user->hasRole('business_admin')) {
            return true;
        }

        return false;
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        if ($user->isBusinessUser() && $user->hasRole('business_admin') && $model->isBusinessUser()) {
            $userBusinessId = $user->profileable->business_id;
            $modelBusinessId = $model->profileable->business_id;

            return $userBusinessId === $modelBusinessId;
        }

        if ($user->isBusinessUser() && $user->hasRole('business_admin') && $model->isCustomer()) {
            $userBusinessId = $user->profileable->business_id;

            return $model->profileable->businesses()
                ->where('business_id', $userBusinessId)
                ->exists();
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            if ($model->isStaff() && $model->profileable->access_level === 'admin') {
                return false;
            }
            return true;
        }

        if ($user->isBusinessUser() && $user->hasRole('business_admin') && $model->isBusinessUser()) {
            $userBusinessId = $user->profileable->business_id;
            $modelBusinessId = $model->profileable->business_id;

            return $userBusinessId === $modelBusinessId;
        }

        return false;
    }

    public function restore(User $user, User $model): bool
    {
        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($user->isStaff() && $user->profileable->access_level === 'admin') {
            return true;
        }

        return false;
    }
}
