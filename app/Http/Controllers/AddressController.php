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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddressController extends BaseController
{
    public function index(FilterAddressesRequest $request, ListAddresses $service): AnonymousResourceCollection
    {
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
        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, Address $address, UpdateAddress $service): JsonResponse
    {
        $this->authorize('update', $address);

        $address = $service->update($address, $request->validated());

        return $this->updatedResponse(
            new AddressResource($address),
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

        // Remove primary flag from other addresses of the same owner
        // This will be implemented when relationships are added

        return $this->successResponse(
            new AddressResource($address),
            'Endereço definido como principal'
        );
    }
}
