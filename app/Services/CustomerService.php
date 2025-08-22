<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Http\Request;

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

    public function registerCustomer(array $data): array
    {
        return $this->registerUser($data);
    }

    public function getCustomers(Request $request)
    {
        return $this->getUsers($request);
    }

    public function getCustomer(Customer $customer)
    {
        return $this->getUser($customer);
    }

    public function createCustomer(array $data)
    {
        return $this->createUser($data);
    }

    public function updateCustomer(Customer $customer, array $data)
    {
        return $this->updateUser($customer, $data);
    }

    public function deleteCustomer(Customer $customer)
    {
        return $this->deleteUser($customer);
    }
}
