<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'cnpj' => $this->cnpj,
            'formatted_cnpj' => $this->formatted_cnpj,
            'email' => $this->email,
            'phone' => $this->phone,
            'description' => $this->description,
            'logo' => $this->logo,
            'status' => $this->status->value,
            'status_label' => $this->getStatusLabel(),
            'is_active' => $this->isActive(),
            'is_approved' => $this->isApproved(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'approved_by' => $this->when($this->relationLoaded('approvedBy'), function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                ];
            }),
            'platform_plan' => $this->when($this->relationLoaded('platformPlan'), function () {
                return $this->platformPlan ? new PlatformPlanResource($this->platformPlan) : null;
            }),
            'addresses' => $this->when($this->relationLoaded('addresses'), function () {
                return AddressResource::collection($this->addresses);
            }),
            'primary_address' => $this->when($this->relationLoaded('primaryAddress'), function () {
                return $this->primaryAddress ? new AddressResource($this->primaryAddress) : null;
            }),
            'users_count' => $this->when($this->relationLoaded('businessUserProfiles'), function () {
                return $this->businessUserProfiles->count();
            }),
            'active_users_count' => $this->when($this->relationLoaded('businessUserProfiles'), function () {
                return $this->businessUserProfiles->where('status', 'active')->count();
            }),
            'customers_count' => $this->when($this->relationLoaded('customers'), function () {
                return $this->customers->count();
            }),
            'has_active_plan' => $this->hasActivePlan(),
            'can_add_more_users' => $this->canAddMoreUsers(),
            'can_add_more_customers' => $this->canAddMoreCustomers(),
            'plan_limits' => $this->when($this->platformPlan, function () {
                return [
                    'max_users' => $this->platformPlan->max_users,
                    'max_customers' => $this->platformPlan->max_customers,
                    'has_unlimited_users' => $this->platformPlan->hasUnlimitedUsers(),
                    'has_unlimited_customers' => $this->platformPlan->hasUnlimitedCustomers(),
                ];
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }

    private function getStatusLabel(): string
    {
        return match ($this->status) {
            \App\Enums\BusinessStatus::Pending => 'Pendente',
            \App\Enums\BusinessStatus::Active => 'Ativo',
            \App\Enums\BusinessStatus::Suspended => 'Suspenso',
            \App\Enums\BusinessStatus::Inactive => 'Inativo',
        };
    }
}
