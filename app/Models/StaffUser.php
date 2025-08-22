<?php

namespace App\Models;

use App\Enums\StaffRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffUser extends User
{
    use HasFactory;

    protected $table = 'staff_users';

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
            'is_active' => 'boolean'
        ]);
    }

    protected static function booted()
    {
        static::created(function (StaffUser $staffUser) {
            $staffUser->assignRole(StaffRole::SUPPORT->value);
        });
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(StaffRole::ADMIN->value);
    }
}