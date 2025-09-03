<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Enums\ProfileStatus;
use App\Enums\CustomerAccessLevel;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpf',
        'birth_date',
        'status',
        'access_level',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'status' => ProfileStatus::class,
            'access_level' => CustomerAccessLevel::class,
        ];
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'profileable');
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_customer_profile');
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

    public function residentialAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Residential);
    }

    public function billingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Billing);
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
        return $query->where('status', ProfileStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === ProfileStatus::Active;
    }

    public function isBasic(): bool
    {
        return $this->access_level === CustomerAccessLevel::Basic;
    }

    public function isPremium(): bool
    {
        return $this->access_level === CustomerAccessLevel::Premium;
    }

    public function isVIP(): bool
    {
        return $this->access_level === CustomerAccessLevel::VIP;
    }
}
