<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'addressable_id' => $this->addressable_id,
            'addressable_type' => $this->addressable_type,
            'addressable' => $this->when($this->relationLoaded('addressable'), function () {
                return $this->getAddressableResource();
            }),
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'formatted_zip_code' => $this->formatted_zip_code,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_primary' => $this->is_primary,
            'type' => $this->type->value,
            'type_label' => $this->getTypeLabel(),
            'full_address' => $this->full_address,
            'has_coordinates' => $this->hasCoordinates(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    private function getAddressableResource()
    {
        if ($this->addressable instanceof \App\Models\Business) {
            return [
                'id' => $this->addressable->id,
                'name' => $this->addressable->name,
                'type' => 'business',
            ];
        }

        if ($this->addressable instanceof \App\Models\CustomerProfile) {
            return [
                'id' => $this->addressable->id,
                'name' => $this->addressable->user?->name,
                'type' => 'customer',
            ];
        }

        return null;
    }

    private function getTypeLabel(): string
    {
        return match ($this->type) {
            \App\Enums\AddressType::Residential => 'Residencial',
            \App\Enums\AddressType::Commercial => 'Comercial',
            \App\Enums\AddressType::Billing => 'Cobrança',
            \App\Enums\AddressType::Shipping => 'Entrega',
        };
    }
}
