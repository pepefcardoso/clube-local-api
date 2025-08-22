<?php

namespace App\Models;

use App\Enums\StaffRole;
use App\Traits\HasFiltering;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffUser extends User
{
    use HasFactory, HasFiltering;

    protected $table = 'staff_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeAdmins($query)
    {
        return $query->role(StaffRole::ADMIN->value);
    }

    protected function getSearchableFields(): array
    {
        return ['name', 'email', 'phone'];
    }

    protected function getFilterableFields(): array
    {
        return [
            'is_active',
            'created_at',
        ];
    }

    protected function getSortableFields(): array
    {
        return [
            'name',
            'email',
            'is_active',
            'created_at',
        ];
    }
}
