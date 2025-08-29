<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Enums\ProfileStatus;
use App\Enums\BusinessAccessLevel;

class BusinessUserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'status',
        'access_level',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProfileStatus::class,
            'access_level' => BusinessAccessLevel::class,
        ];
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'profileable');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProfileStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === ProfileStatus::Active;
    }

    public function isUser(): bool
    {
        return $this->access_level === BusinessAccessLevel::User;
    }

    public function isManager(): bool
    {
        return $this->access_level === BusinessAccessLevel::Manager;
    }

    public function isAdmin(): bool
    {
        return $this->access_level === BusinessAccessLevel::Admin;
    }

    public function canManageUsers(): bool
    {
        return $this->isManager() || $this->isAdmin();
    }

    public function canManageBusiness(): bool
    {
        return $this->isAdmin();
    }
}
