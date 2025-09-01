<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdvanced() || $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->canManageUsers();
        }

        return $user->isCustomer();
    }

    public function view(User $user, Address $address): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdvanced() || $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->canManageUsers();
        }

        if ($user->isCustomer()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->canManageUsers();
        }

        return $user->isCustomer();
    }

    public function update(User $user, Address $address): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->canManageUsers();
        }

        if ($user->isCustomer()) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Address $address): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        if ($user->isBusinessUser()) {
            return $user->profileable->canManageUsers();
        }

        if ($user->isCustomer()) {
            return true;
        }

        return false;
    }
}
