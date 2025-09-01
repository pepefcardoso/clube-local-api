<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
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
        ];
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

    public function isResidential(): bool
    {
        return $this->type === 'residential';
    }

    public function isCommercial(): bool
    {
        return $this->type === 'commercial';
    }

    public function isBilling(): bool
    {
        return $this->type === 'billing';
    }

    public function isShipping(): bool
    {
        return $this->type === 'shipping';
    }

    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }
}
