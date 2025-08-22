<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user instanceof \App\Models\StaffUser;
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user instanceof Customer && $user->id === $customer->id) {
            return true;
        }

        return $user instanceof \App\Models\StaffUser;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user instanceof Customer && $user->id === $customer->id) {
            return true;
        }

        return $user instanceof \App\Models\StaffUser;
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($user instanceof Customer && $user->id === $customer->id) {
            return true;
        }

        return $user instanceof \App\Models\StaffUser;
    }
}
