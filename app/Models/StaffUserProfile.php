<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Enums\StaffAccessLevel;

class StaffUserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'access_level',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProfileStatus::class,
            'access_level' => StaffAccessLevel::class,
        ];
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'profileable');
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
        return $this->isAdvanced() || $this->isAdmin();
    }

    public function canManageBusinesses(): bool
    {
        return $this->isAdvanced() || $this->isAdmin();
    }

    public function canAccessSystemSettings(): bool
    {
        return $this->isAdmin();
    }
}
