<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerService
{
    public function getCustomers(Request $request): LengthAwarePaginator
    {
        return Customer::query()
            ->with('roles:id,name')
            ->filter($request)
            ->when(!$request->has('sort'), fn($q) => $q->latest())
            ->paginate($request->get('per_page', 15));
    }

    public function getCustomer(Customer $customer): Customer
    {
        return $customer->load('roles', 'permissions');
    }

    public function createCustomer(array $data): Customer
    {
        $data['password'] = Hash::make($data['password']);

        $customer = Customer::create($data);

        return $customer->load('roles');
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $customer->update($data);

        return $customer->load('roles');
    }

    public function deleteCustomer(Customer $customer): bool
    {
        $customer->tokens()->delete();

        return $customer->delete();
    }

    public function registerCustomer(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $data['subscription_type'] = 'basic';

        $customer = Customer::create($data);

        $token = $customer->createToken(
            name: 'customer_token',
            abilities: ['customer:read', 'customer:update'],
            expiresAt: now()->addDays(30)
        );

        return [
            'customer' => $customer->load('roles'),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }
}
