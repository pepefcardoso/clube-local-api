<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffUserResource extends JsonResource
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
            'roles' => $this->whenLoaded('roles', fn() => $this->getRoleNames()->toArray(), $this->getRoleNames()->toArray()),
            'is_admin' => method_exists($this, 'isAdmin') ? (bool) $this->isAdmin() : $this->hasRole('admin'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
