<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'country',
        'latitude',
        'longitude',
        'is_primary',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'type' => AddressType::class,
        ];
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getFullAddressAttribute(): string
    {
        $address = "{$this->street}, {$this->number}";

        if ($this->complement) {
            $address .= ", {$this->complement}";
        }

        $address .= ", {$this->neighborhood}, {$this->city} - {$this->state}, {$this->zip_code}";

        if ($this->country !== 'BR') {
            $address .= ", {$this->country}";
        }

        return $address;
    }

    public function getFormattedZipCodeAttribute(): string
    {
        if (strlen($this->zip_code) === 8) {
            return substr($this->zip_code, 0, 5) . '-' . substr($this->zip_code, 5);
        }

        return $this->zip_code;
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopeByState($query, string $state)
    {
        return $query->where('state', $state);
    }

    public function scopeByZipCode($query, string $zipCode)
    {
        $cleanZipCode = preg_replace('/[^0-9]/', '', $zipCode);
        return $query->where('zip_code', $cleanZipCode);
    }

    public function scopeForEntity($query, $entity)
    {
        return $query->where('addressable_id', $entity->id)
            ->where('addressable_type', get_class($entity));
    }

    public function isResidential(): bool
    {
        return $this->type === AddressType::Residential;
    }

    public function isCommercial(): bool
    {
        return $this->type === AddressType::Commercial;
    }

    public function isBilling(): bool
    {
        return $this->type === AddressType::Billing;
    }

    public function isShipping(): bool
    {
        return $this->type === AddressType::Shipping;
    }

    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Address $address) {
            if ($address->is_primary && $address->addressable_id && $address->addressable_type) {
                static::where('addressable_id', $address->addressable_id)
                    ->where('addressable_type', $address->addressable_type)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
