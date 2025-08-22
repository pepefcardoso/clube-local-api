<?php

namespace App\Models;

use App\Enums\CustomerRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends User
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'birth_date',
        'address',
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
            $role = $customer->subscription_type === 'premium'
                ? CustomerRole::PREMIUM->value
                : CustomerRole::BASIC->value;

            $customer->assignRole($role);
        });
    }

    public function isPremium(): bool
    {
        return $this->subscription_type === CustomerRole::PREMIUM->value;
    }
}
