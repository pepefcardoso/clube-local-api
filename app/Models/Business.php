<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\BusinessStatus;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'cnpj',
        'email',
        'phone',
        'description',
        'logo',
        'status',
        'approved_at',
        'approved_by',
        'platform_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'status' => BusinessStatus::class,
        ];
    }

    public function businessUserProfiles(): HasMany
    {
        return $this->hasMany(BusinessUserProfile::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(CustomerProfile::class, 'business_customer_profile');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function platformPlan(): BelongsTo
    {
        return $this->belongsTo(PlatformPlan::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function primaryAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('is_primary', true);
    }

    public function getAddressByType(AddressType $type)
    {
        return $this->addresses()->where('type', $type)->first();
    }

    public function billingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Billing);
    }

    public function commercialAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Commercial);
    }

    public function shippingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', AddressType::Shipping);
    }

    public function hasAddressOfType(AddressType $type): bool
    {
        return $this->addresses()->where('type', $type)->exists();
    }

    public function setAddressForType(AddressType $type, array $addressData): Address
    {
        $addressData['type'] = $type;
        $addressData['addressable_id'] = $this->id;
        $addressData['addressable_type'] = static::class;

        $existingAddress = $this->addresses()->where('type', $type)->first();

        if ($existingAddress) {
            $existingAddress->update($addressData);
            return $existingAddress;
        }

        return $this->addresses()->create($addressData);
    }

    public function getFormattedCnpjAttribute(): ?string
    {
        if (!$this->cnpj || strlen($this->cnpj) !== 14) {
            return $this->cnpj;
        }

        return substr($this->cnpj, 0, 2) . '.' .
            substr($this->cnpj, 2, 3) . '.' .
            substr($this->cnpj, 5, 3) . '/' .
            substr($this->cnpj, 8, 4) . '-' .
            substr($this->cnpj, 12, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', BusinessStatus::Active);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')->where('status', BusinessStatus::Active);
    }

    public function scopePending($query)
    {
        return $query->where('status', BusinessStatus::Pending);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', BusinessStatus::Suspended);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', BusinessStatus::Inactive);
    }

    public function scopeWithActivePlan($query)
    {
        return $query->whereHas('platformPlan', function ($q) {
            $q->where('is_active', true);
        });
    }

    public function scopeWithoutPlan($query)
    {
        return $query->whereNull('platform_plan_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByCnpj($query, string $cnpj)
    {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return $query->where('cnpj', $cleanCnpj);
    }

    public function isActive(): bool
    {
        return $this->status === BusinessStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === BusinessStatus::Pending;
    }

    public function isSuspended(): bool
    {
        return $this->status === BusinessStatus::Suspended;
    }

    public function isInactive(): bool
    {
        return $this->status === BusinessStatus::Inactive;
    }

    public function isApproved(): bool
    {
        return !is_null($this->approved_at) && $this->isActive();
    }

    public function hasActivePlan(): bool
    {
        return $this->platformPlan && $this->platformPlan->is_active;
    }

    public function canAddMoreUsers(): bool
    {
        if (!$this->hasActivePlan()) {
            return false;
        }

        if ($this->platformPlan->hasUnlimitedUsers()) {
            return true;
        }

        return $this->businessUserProfiles()->count() < $this->platformPlan->max_users;
    }

    public function canAddMoreCustomers(): bool
    {
        if (!$this->hasActivePlan()) {
            return false;
        }

        if ($this->platformPlan->hasUnlimitedCustomers()) {
            return true;
        }

        return $this->customers()->count() < $this->platformPlan->max_customers;
    }

    public function getRemainingUserSlots(): int
    {
        if (!$this->hasActivePlan() || $this->platformPlan->hasUnlimitedUsers()) {
            return PHP_INT_MAX;
        }

        return max(0, $this->platformPlan->max_users - $this->businessUserProfiles()->count());
    }

    public function getRemainingCustomerSlots(): int
    {
        if (!$this->hasActivePlan() || $this->platformPlan->hasUnlimitedCustomers()) {
            return PHP_INT_MAX;
        }

        return max(0, $this->platformPlan->max_customers - $this->customers()->count());
    }

    public function approve(User $approver): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        return $this->update([
            'status' => BusinessStatus::Active,
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);
    }

    public function suspend(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->update(['status' => BusinessStatus::Suspended]);
    }

    public function activate(): bool
    {
        return $this->update(['status' => BusinessStatus::Active]);
    }

    public function deactivate(): bool
    {
        return $this->update(['status' => BusinessStatus::Inactive]);
    }

    public function assignPlan(PlatformPlan $plan): bool
    {
        return $this->update(['platform_plan_id' => $plan->id]);
    }

    public function removePlan(): bool
    {
        return $this->update(['platform_plan_id' => null]);
    }

    public function getStats(): array
    {
        return [
            'total_users' => $this->businessUserProfiles()->count(),
            'active_users' => $this->businessUserProfiles()->active()->count(),
            'total_customers' => $this->customers()->count(),
            'total_addresses' => $this->addresses()->count(),
            'has_active_plan' => $this->hasActivePlan(),
            'can_add_users' => $this->canAddMoreUsers(),
            'can_add_customers' => $this->canAddMoreCustomers(),
            'remaining_user_slots' => $this->getRemainingUserSlots(),
            'remaining_customer_slots' => $this->getRemainingCustomerSlots(),
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Business $business) {
            if (empty($business->slug)) {
                $business->slug = \Illuminate\Support\Str::slug($business->name);
            }

            $originalSlug = $business->slug;
            $counter = 1;

            while (static::where('slug', $business->slug)->exists()) {
                $business->slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        });

        static::updating(function (Business $business) {
            if ($business->isDirty('name') && empty($business->slug)) {
                $business->slug = \Illuminate\Support\Str::slug($business->name);
            }
        });
    }
}
