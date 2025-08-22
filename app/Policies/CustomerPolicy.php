<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->isSelfOrAdmin($user, $customer);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->isSelfOrAdmin($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->isSelfOrAdmin($user, $customer);
    }
}
