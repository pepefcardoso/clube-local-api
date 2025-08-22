<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService extends BaseUserService
{
    protected function getModel(): string
    {
        return Customer::class;
    }

    protected function getDefaultData(): array
    {
        return [];
    }
}
