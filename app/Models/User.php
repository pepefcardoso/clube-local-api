<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

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

        $abilities = $this->getUserAbilities();

        return $this->createToken('auth-token', $abilities)->plainTextToken;
    }

    public function getUserAbilities(): array
    {
        $abilities = [];

        // Base abilities for all users
        $abilities[] = 'profile:read';
        $abilities[] = 'profile:update';

        if ($this->isCustomer()) {
            $abilities = array_merge($abilities, [
                'customer:profile:read',
                'customer:profile:update',
                'orders:create',
                'orders:read',
            ]);
        }

        if ($this->isStaff()) {
            $staffProfile = $this->profileable;

            if ($staffProfile && $staffProfile->access_level === 'admin') {
                $abilities = array_merge($abilities, [
                    'admin:users:read',
                    'admin:users:create',
                    'admin:users:update',
                    'admin:users:delete',
                    'admin:staff:create',
                    'admin:staff:update',
                    'admin:staff:delete',
                    'admin:businesses:read',
                    'admin:businesses:approve',
                    'admin:system:manage',
                ]);
            } elseif ($staffProfile && $staffProfile->access_level === 'advanced') {
                $abilities = array_merge($abilities, [
                    'staff:dashboard:read',
                    'staff:reports:read',
                    'staff:users:read',
                ]);
            } else {
                $abilities = array_merge($abilities, [
                    'staff:dashboard:read',
                    'staff:reports:read',
                ]);
            }
        }

        if ($this->isBusinessUser()) {
            $businessProfiles = $this->businessUserProfiles()->active()->get();
            foreach ($businessProfiles as $profile) {
                $businessId = $profile->business_id;

                if ($this->hasRole('business_admin')) {
                    $abilities = array_merge($abilities, [
                        "business:{$businessId}:manage",
                        "business:{$businessId}:users:manage",
                        "business:{$businessId}:settings:update",
                    ]);
                } else {
                    $abilities = array_merge($abilities, [
                        "business:{$businessId}:read",
                        "business:{$businessId}:orders:read",
                    ]);
                }
            }
        }

        return array_unique($abilities);
    }

    public function getAllUserRoles(): array
    {
        $roles = [];

        if ($this->isCustomer()) {
            $roles[] = 'customer';
        }

        if ($this->isBusinessUser()) {
            $roles[] = 'business_user';

            $hasAdminRole = $this->businessUserProfiles()
                ->whereHas('business', function ($query) {
                    $query->where('status', 'active');
                })
                ->where('permissions', 'like', '%admin%')
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
