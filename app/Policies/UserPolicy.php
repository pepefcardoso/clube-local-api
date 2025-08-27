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

        if ($user->isBusinessUser()) {
            $userBusinessIds = $user->businessUserProfiles->pluck('business_id')->toArray();

            if ($model->isBusinessUser()) {
                $modelBusinessIds = $model->businessUserProfiles->pluck('business_id')->toArray();
                return !empty(array_intersect($userBusinessIds, $modelBusinessIds));
            }

            if ($model->isCustomer()) {
                $modelBusinessIds = $model->profileable->businesses->pluck('id')->toArray();
                return !empty(array_intersect($userBusinessIds, $modelBusinessIds));
            }
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

        if ($user->isBusinessUser() && $user->hasRole('business_admin')) {
            $userBusinessIds = $user->businessUserProfiles->pluck('business_id')->toArray();

            if ($model->isBusinessUser()) {
                $modelBusinessIds = $model->businessUserProfiles->pluck('business_id')->toArray();
                return !empty(array_intersect($userBusinessIds, $modelBusinessIds));
            }

            if ($model->isCustomer()) {
                $modelBusinessIds = $model->profileable->businesses->pluck('id')->toArray();
                return !empty(array_intersect($userBusinessIds, $modelBusinessIds));
            }
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

        if ($user->isBusinessUser() && $user->hasRole('business_admin')) {
            $userBusinessIds = $user->businessUserProfiles->pluck('business_id')->toArray();

            if ($model->isBusinessUser()) {
                $modelBusinessIds = $model->businessUserProfiles->pluck('business_id')->toArray();
                return !empty(array_intersect($userBusinessIds, $modelBusinessIds));
            }
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
