<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\Collections\CustomerCollection;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseApiController
{
    public function __construct(
        private CustomerService $customerService
    ) {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request): JsonResponse
    {
        $customers = $this->customerService->getCustomers($request);

        return $this->collectionResponse(
            new CustomerCollection($customers)
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return $this->resourceResponse(
            new CustomerResource($customer),
            'Customer created successfully'
        )->setStatusCode(201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer = $this->customerService->getCustomer($customer);

        return $this->resourceResponse(
            new CustomerResource($customer)
        );
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($customer, $request->validated());

        return $this->resourceResponse(
            new CustomerResource($customer),
            'Customer updated successfully'
        );
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->deleteCustomer($customer);

        return $this->successResponse(
            message: 'Customer deleted successfully'
        );
    }

    public function register(StoreCustomerRequest $request): JsonResponse
    {
        $result = $this->customerService->registerCustomer($request->validated());

        return response()->json([
            'message' => 'Customer registered successfully',
            'user' => [
                'id' => $result['customer']->id,
                'name' => $result['customer']->name,
                'email' => $result['customer']->email,
                'type' => 'customer',
                'roles' => $result['customer']->getRoleNames(),
            ],
            'token' => $result['token'],
            'expires_at' => $result['expires_at'],
        ], 201);
    }
}
