<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Enums\StaffAccessLevel;

class StaffUserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_level',
        'system_permissions',
    ];

    protected function casts(): array
    {
        return [
            'system_permissions' => 'array',
            'access_level' => StaffAccessLevel::class,
        ];
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'profileable');
    }

    public function hasSystemPermission(string $permission): bool
    {
        return in_array($permission, $this->system_permissions ?? []);
    }

    public function isAdmin(): bool
    {
        return $this->access_level === StaffAccessLevel::Admin;
    }

    public function isAdvanced(): bool
    {
        return $this->access_level === StaffAccessLevel::Advanced;
    }

    public function isBasic(): bool
    {
        return $this->access_level === StaffAccessLevel::Basic;
    }

    public function canCreateStaff(): bool
    {
        return $this->isAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->hasSystemPermission('admin:users:read') || $this->isAdmin();
    }

    public function canManageBusinesses(): bool
    {
        return $this->hasSystemPermission('admin:businesses:read') || $this->isAdmin();
    }

    public function canAccessSystemSettings(): bool
    {
        return $this->hasSystemPermission('admin:system:manage') || $this->isAdmin();
    }
}
