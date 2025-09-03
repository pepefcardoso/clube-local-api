<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\BusinessStatus;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'cnpj',
        'email',
        'phone',
        'description',
        'logo',
        'status',
        'approved_at',
        'approved_by',
        'platform_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'status' => BusinessStatus::class,
        ];
    }

    public function businessUserProfiles(): HasMany
    {
        return $this->hasMany(BusinessUserProfile::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(CustomerProfile::class, 'business_customer_profile');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function platformPlan(): BelongsTo
    {
        return $this->belongsTo(PlatformPlan::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function primaryAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('is_primary', true);
    }

    public function getAddressByType(AddressType $type)
    {
        return $this->addresses()->where('type', $type)->first();
    }

    public function billingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Billing);
    }

    public function commercialAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Commercial);
    }

    public function shippingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Shipping);
    }

    public function hasAddressOfType(AddressType $type): bool
    {
        return $this->addresses()->where('type', $type)->exists();
    }

    public function setAddressForType(AddressType $type, array $addressData): Address
    {
        $addressData['type'] = $type;
        $addressData['addressable_id'] = $this->id;
        $addressData['addressable_type'] = static::class;

        $existingAddress = $this->addresses()->where('type', $type)->first();

        if ($existingAddress) {
            $existingAddress->update($addressData);
            return $existingAddress;
        }

        return $this->addresses()->create($addressData);
    }

    public function scopeActive($query)
    {
        return $query->where('status', BusinessStatus::Active);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')->where('status', BusinessStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === BusinessStatus::Active;
    }

    public function isApproved(): bool
    {
        return !is_null($this->approved_at) && $this->isActive();
    }

    public function hasActivePlan(): bool
    {
        return $this->platformPlan && $this->platformPlan->is_active;
    }

    public function canAddMoreUsers(): bool
    {
        if (!$this->hasActivePlan()) {
            return false;
        }

        if ($this->platformPlan->hasUnlimitedUsers()) {
            return true;
        }

        return $this->businessUserProfiles()->count() < $this->platformPlan->max_users;
    }

    public function canAddMoreCustomers(): bool
    {
        if (!$this->hasActivePlan()) {
            return false;
        }

        if ($this->platformPlan->hasUnlimitedCustomers()) {
            return true;
        }

        return $this->customers()->count() < $this->platformPlan->max_customers;
    }
}
