<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'provider',
        'provider_id',
        'last_login_at',
        'is_active',
        'profileable_id',
        'profileable_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function profileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCustomer(): bool
    {
        return $this->profileable_type === CustomerProfile::class;
    }

    public function isBusinessUser(): bool
    {
        return $this->profileable_type === BusinessUserProfile::class && $this->profileable?->isActive();
    }

    public function isStaff(): bool
    {
        return $this->profileable_type === StaffUserProfile::class;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function generateApiToken(): string
    {
        $this->tokens()->delete();
        return $this->createToken('auth-token')->plainTextToken;
    }
}
