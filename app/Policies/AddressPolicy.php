<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;
use App\Models\Business;
use App\Models\CustomerProfile;

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

        return $this->hasAccessToAddressable($user, $address);
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

        return $this->hasAccessToAddressable($user, $address);
    }

    public function delete(User $user, Address $address): bool
    {
        if ($user->isStaff()) {
            return $user->profileable->isAdmin();
        }

        return $this->hasAccessToAddressable($user, $address);
    }

    private function hasAccessToAddressable(User $user, Address $address): bool
    {
        if (!$address->addressable) {
            return false;
        }

        if ($address->addressable instanceof Business) {
            if ($user->isBusinessUser()) {
                return $user->profileable->business_id === $address->addressable->id &&
                    ($user->profileable->canManageUsers() || $user->profileable->isAdmin());
            }
        }

        if ($address->addressable instanceof CustomerProfile) {
            if ($user->isCustomer()) {
                return $user->profileable->id === $address->addressable->id;
            }

            if ($user->isBusinessUser()) {
                return $address->addressable->businesses()
                    ->where('business_id', $user->profileable->business_id)
                    ->exists() && $user->profileable->canManageUsers();
            }
        }

        return false;
    }
}
