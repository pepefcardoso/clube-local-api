<?php

namespace App\Http\Controllers;

use App\Http\Requests\Address\FilterAddressesRequest;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\Address\CreateAddress;
use App\Services\Address\UpdateAddress;
use App\Services\Address\DeleteAddress;
use App\Services\Address\ListAddresses;
use App\Models\Address;
use App\Models\Business;
use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddressController extends BaseController
{
    public function index(FilterAddressesRequest $request, ListAddresses $service): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Address::class);

        $addresses = $service->list($request->validated());
        return AddressResource::collection($addresses);
    }

    public function store(StoreAddressRequest $request, CreateAddress $service): JsonResponse
    {
        $this->authorize('create', Address::class);

        $address = $service->create($request->validated());

        return $this->createdResponse(
            new AddressResource($address),
            'Endereço criado com sucesso'
        );
    }

    public function show(Address $address): AddressResource
    {
        $this->authorize('view', $address);

        $address->load('addressable');
        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, Address $address, UpdateAddress $service): JsonResponse
    {
        $this->authorize('update', $address);

        $updatedAddress = $service->update($address, $request->validated());

        return $this->updatedResponse(
            new AddressResource($updatedAddress),
            'Endereço atualizado com sucesso'
        );
    }

    public function destroy(Address $address, DeleteAddress $service): JsonResponse
    {
        $this->authorize('delete', $address);

        $service->delete($address);

        return $this->deletedResponse('Endereço excluído com sucesso');
    }

    public function setPrimary(Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address->update(['is_primary' => true]);

        return $this->successResponse(
            new AddressResource($address->fresh()),
            'Endereço definido como principal'
        );
    }

    public function getBusinessAddresses(Business $business): AnonymousResourceCollection
    {
        $this->authorize('view', $business);

        $addresses = $business->addresses()
            ->orderBy('is_primary', 'desc')
            ->orderBy('type')
            ->get();

        return AddressResource::collection($addresses);
    }

    public function storeBusinessAddress(StoreAddressRequest $request, Business $business, CreateAddress $service): JsonResponse
    {
        $this->authorize('update', $business);

        $addressData = $request->validated();
        $addressData['addressable_id'] = $business->id;
        $addressData['addressable_type'] = Business::class;

        $address = $service->create($addressData);

        return $this->createdResponse(
            new AddressResource($address),
            'Endereço da empresa criado com sucesso'
        );
    }

    public function getCustomerAddresses(CustomerProfile $customer): AnonymousResourceCollection
    {
        $this->authorize('view', $customer);

        $addresses = $customer->addresses()
            ->orderBy('is_primary', 'desc')
            ->orderBy('type')
            ->get();

        return AddressResource::collection($addresses);
    }

    public function storeCustomerAddress(StoreAddressRequest $request, CustomerProfile $customer, CreateAddress $service): JsonResponse
    {
        $this->authorize('update', $customer);

        $addressData = $request->validated();
        $addressData['addressable_id'] = $customer->id;
        $addressData['addressable_type'] = CustomerProfile::class;

        $address = $service->create($addressData);

        return $this->createdResponse(
            new AddressResource($address),
            'Endereço do cliente criado com sucesso'
        );
    }
}
