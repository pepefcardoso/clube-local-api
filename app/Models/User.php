<?php

namespace App\Models;

use App\Traits\HasUserAbilities;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes, HasUserAbilities;

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

    public function businessUserProfiles(): HasMany
    {
        return $this->hasMany(BusinessUserProfile::class, 'user_id');
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

    // Utility methods
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getAllUserRoles(): array
    {
        $roles = [];

        if ($this->isCustomer()) {
            $roles[] = 'customer';
        }

        if ($this->isBusinessUser()) {
            $roles[] = 'business_user';

            // Check if user has admin role in any business
            $hasAdminRole = $this->businessUserProfiles()
                ->whereHas('business', function ($query) {
                    $query->where('status', 'active');
                })
                ->where(function ($query) {
                    $query->whereJsonContains('permissions', 'admin')
                        ->orWhereJsonContains('permissions', 'manage_users')
                        ->orWhereJsonContains('permissions', 'full_access');
                })
                ->exists();

            if ($hasAdminRole) {
                $roles[] = 'business_admin';
            }
        }

        if ($this->isStaff()) {
            $staffProfile = $this->profileable;
            if ($staffProfile) {
                switch ($staffProfile->access_level) {
                    case 'admin':
                        $roles[] = 'staff_admin';
                        break;
                    case 'advanced':
                        $roles[] = 'staff_advanced';
                        break;
                    default:
                        $roles[] = 'staff_basic';
                }
            }
        }

        return $roles;
    }

    public function hasBusinessAdminPermission(int $businessId): bool
    {
        if (!$this->isBusinessUser()) {
            return false;
        }

        return $this->businessUserProfiles()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereJsonContains('permissions', 'admin')
                    ->orWhereJsonContains('permissions', 'manage_users')
                    ->orWhereJsonContains('permissions', 'full_access');
            })
            ->exists();
    }
}
