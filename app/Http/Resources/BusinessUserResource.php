<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessUserResource extends JsonResource
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
            'is_active' => $this->when(isset($this->is_active), (bool) $this->is_active),
            'subscription_type' => $this->when(isset($this->subscription_type), $this->subscription_type),
            'roles' => $this->whenLoaded('roles', fn() => $this->getRoleNames()->toArray(), $this->getRoleNames()->toArray()),
            'is_manager' => method_exists($this, 'isManager') ? (bool) $this->isManager() : false,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
