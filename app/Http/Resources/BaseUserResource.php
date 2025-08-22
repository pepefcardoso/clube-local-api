<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->when(isset($this->phone), $this->phone),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];

        // Campos específicos por tipo
        return array_merge($data, $this->getTypeSpecificData());
    }

    protected function getTypeSpecificData(): array
    {
        return match (get_class($this->resource)) {
            \App\Models\Customer::class => [
                'birth_date' => $this->when(isset($this->birth_date), $this->birth_date?->toDateString()),
                'address' => $this->when(isset($this->address), $this->address),
            ],
            \App\Models\BusinessUser::class => [
                'is_active' => (bool) $this->is_active,
            ],
            \App\Models\StaffUser::class => [
                'is_active' => (bool) $this->is_active,
            ],
            default => [],
        };
    }
}
