<?php

namespace App\Models;

use App\Enums\BusinessUserRole;
use App\Traits\HasFiltering;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessUser extends User
{
    use HasFactory, HasFiltering;

    protected $table = 'business_users';

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
        static::created(function (BusinessUser $businessUser) {
            $businessUser->assignRole(BusinessUserRole::EMPLOYEE->value);
        });
    }

    public function isManager(): bool
    {
        return $this->hasRole(BusinessUserRole::MANAGER->value);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeManagers($query)
    {
        return $query->role(BusinessUserRole::MANAGER->value);
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
