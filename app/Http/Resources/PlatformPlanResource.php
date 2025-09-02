<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'billing_cycle' => $this->billing_cycle,
            'billing_cycle_text' => $this->billing_cycle_text,
            'features' => $this->features,
            'max_users' => $this->max_users,
            'max_customers' => $this->max_customers,
            'has_unlimited_users' => $this->hasUnlimitedUsers(),
            'has_unlimited_customers' => $this->hasUnlimitedCustomers(),
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_free' => $this->isFree(),
            'sort_order' => $this->sort_order,
            'businesses_count' => $this->whenCounted('businesses'),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
