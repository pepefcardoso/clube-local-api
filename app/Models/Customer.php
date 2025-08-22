<?php

namespace App\Models;

use App\Enums\CustomerRole;
use App\Traits\HasFiltering;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends User
{
    use HasFactory, HasFiltering;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'birth_date',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'birth_date' => 'date',
        ]);
    }

    protected static function booted()
    {
        static::created(function (Customer $customer) {
            $role = CustomerRole::BASIC->value;
            $customer->assignRole($role);
        });
    }

    public function isPremium(): bool
    {
        return $this->hasRole(CustomerRole::PREMIUM->value);
    }


    public function scopeActive($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    protected function getSearchableFields(): array
    {
        return ['name', 'email', 'phone'];
    }

    protected function getFilterableFields(): array
    {
        return [
            'created_at',
            'email_verified_at'
        ];
    }

    protected function getSortableFields(): array
    {
        return [
            'name',
            'email',
            'created_at',
            'birth_date'
        ];
    }
}
