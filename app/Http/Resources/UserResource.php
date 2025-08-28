<?php

namespace App\Http\Resources;

use App\Models\BusinessUserProfile;
use App\Models\CustomerProfile;
use App\Models\StaffUserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'provider' => $this->provider,
            'provider_id' => $this->provider_id,
            'last_login_at' => $this->last_login_at?->toDateTimeString(),
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'profile_type' => $this->getProfileType(),
            // 'profile' => $this->getProfileData(),
            'roles' => $this->getAllUserRoles(),
            // 'is_customer' => $this->isCustomer(),
            // 'is_business_user' => $this->isBusinessUser(),
            // 'is_staff' => $this->isStaff(),
        ];
    }

    private function getProfileType(): ?string
    {
        if (!$this->profileable_type) {
            return null;
        }

        return match ($this->profileable_type) {
            CustomerProfile::class => 'customer',
            BusinessUserProfile::class => 'business',
            StaffUserProfile::class => 'staff',
            default => null
        };
    }

    // private function getProfileData(): ?array
    // {
    //     if (!$this->relationLoaded('profileable') || !$this->profileable) {
    //         return null;
    //     }

    //     if ($this->profileable instanceof CustomerProfile) {
    //         return [
    //             'id' => $this->profileable->id,
    //             'cpf' => $this->profileable->cpf,
    //             'birth_date' => $this->profileable->birth_date?->toDateString(),
    //             'status' => $this->profileable->status->value,
    //             'created_at' => $this->profileable->created_at->toDateTimeString(),
    //             'updated_at' => $this->profileable->updated_at->toDateTimeString(),
    //         ];
    //     }

    //     if ($this->profileable instanceof BusinessUserProfile) {
    //         return [
    //             'id' => $this->profileable->id,
    //             'business_id' => $this->profileable->business_id,
    //             'status' => $this->profileable->status->value,
    //             'permissions' => $this->profileable->permissions,
    //             'business' => $this->whenLoaded('profileable.business', function () {
    //                 return new BusinessResource($this->profileable->business);
    //             }),
    //             'created_at' => $this->profileable->created_at->toDateTimeString(),
    //             'updated_at' => $this->profileable->updated_at->toDateTimeString(),
    //         ];
    //     }

    //     if ($this->profileable instanceof StaffUserProfile) {
    //         return [
    //             'id' => $this->profileable->id,
    //             'access_level' => $this->profileable->access_level->value,
    //             'system_permissions' => $this->profileable->system_permissions,
    //             'created_at' => $this->profileable->created_at->toDateTimeString(),
    //             'updated_at' => $this->profileable->updated_at->toDateTimeString(),
    //         ];
    //     }

    //     return null;
    // }
}
