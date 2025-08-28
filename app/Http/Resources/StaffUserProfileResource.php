<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffUserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'access_level' => $this->access_level->value,
            'system_permissions' => $this->system_permissions,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'avatar' => $this->user->avatar,
                    'last_login_at' => $this->user->last_login_at?->toDateTimeString(),
                    'is_active' => $this->user->is_active,
                    'email_verified_at' => $this->user->email_verified_at?->toDateTimeString(),
                    'created_at' => $this->user->created_at->toDateTimeString(),
                    'updated_at' => $this->user->updated_at->toDateTimeString(),
                ];
            }),
            'can_manage_users' => $this->canManageUsers(),
            'can_manage_businesses' => $this->canManageBusinesses(),
            'can_manage_system' => $this->canManageSystem(),
        ];
    }

    private function canManageUsers(): bool
    {
        return $this->hasSystemPermission('admin:users:read') ||
            $this->access_level === \App\Enums\StaffAccessLevel::Admin;
    }

    private function canManageBusinesses(): bool
    {
        return $this->hasSystemPermission('admin:businesses:read') ||
            $this->access_level === \App\Enums\StaffAccessLevel::Admin;
    }

    private function canManageSystem(): bool
    {
        return $this->hasSystemPermission('admin:system:manage') ||
            $this->access_level === \App\Enums\StaffAccessLevel::Admin;
    }
}
