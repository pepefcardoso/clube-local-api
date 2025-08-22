<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->when(isset($this->phone), $this->phone),
            'birth_date' => $this->when(isset($this->birth_date), $this->birth_date?->toDateString()),
            'address' => $this->when(isset($this->address), $this->address),
            'subscription_type' => $this->when(isset($this->subscription_type), $this->subscription_type),
            'roles' => $this->whenLoaded('roles', fn() => $this->getRoleNames()->toArray(), $this->getRoleNames()->toArray()),
            'is_premium' => method_exists($this, 'isPremium') ? (bool) $this->isPremium() : ($this->subscription_type === 'premium'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
