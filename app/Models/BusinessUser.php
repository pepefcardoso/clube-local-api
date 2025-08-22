<?php

namespace App\Models;

use App\Enums\BusinessUserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessUser extends User
{
    use HasFactory;

    protected $table = 'business_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }

    protected static function booted()
    {
        static::created(function (BusinessUser $businessUser) {
            $businessUser->assignRole(BusinessUserRole::EMPLOYEE->value);
        });
    }

    public function isManager(): bool
    {
        return $this->hasRole(BusinessUserRole::MANAGER->value);
    }
}
