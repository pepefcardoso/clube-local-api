<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'features',
        'max_users',
        'max_customers',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function scopeByBillingCycle($query, string $cycle)
    {
        return $query->where('billing_cycle', $cycle);
    }

    public function hasUnlimitedUsers(): bool
    {
        return is_null($this->max_users) || $this->max_users === -1;
    }

    public function hasUnlimitedCustomers(): bool
    {
        return is_null($this->max_customers) || $this->max_customers === -1;
    }

    public function hasUserLimit(): bool
    {
        return !$this->hasUnlimitedUsers();
    }

    public function hasCustomerLimit(): bool
    {
        return !$this->hasUnlimitedCustomers();
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    public function getYearlyPrice(): float
    {
        return match ($this->billing_cycle) {
            'monthly' => $this->price * 12,
            'quarterly' => $this->price * 4,
            'semi_annual' => $this->price * 2,
            'yearly' => $this->price,
            default => $this->price * 12,
        };
    }

    public function getMonthlyPrice(): float
    {
        return match ($this->billing_cycle) {
            'monthly' => $this->price,
            'quarterly' => $this->price / 3,
            'semi_annual' => $this->price / 6,
            'yearly' => $this->price / 12,
            default => $this->price,
        };
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function addFeature(string $feature): bool
    {
        $features = $this->features ?? [];

        if (!in_array($feature, $features)) {
            $features[] = $feature;
            return $this->update(['features' => $features]);
        }

        return false;
    }

    public function removeFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        $key = array_search($feature, $features);

        if ($key !== false) {
            unset($features[$key]);
            return $this->update(['features' => array_values($features)]);
        }

        return false;
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function toggleFeatured(): bool
    {
        return $this->update(['is_featured' => !$this->is_featured]);
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public function isPaid(): bool
    {
        return $this->price > 0;
    }

    public function getActiveBusinessesCount(): int
    {
        return $this->businesses()->active()->count();
    }

    public function getTotalBusinessesCount(): int
    {
        return $this->businesses()->count();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (PlatformPlan $plan) {
            if (empty($plan->slug)) {
                $plan->slug = \Illuminate\Support\Str::slug($plan->name);
            }

            $originalSlug = $plan->slug;
            $counter = 1;

            while (static::where('slug', $plan->slug)->exists()) {
                $plan->slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            if (is_null($plan->sort_order)) {
                $maxOrder = static::max('sort_order') ?? 0;
                $plan->sort_order = $maxOrder + 1;
            }
        });

        static::updating(function (PlatformPlan $plan) {
            if ($plan->isDirty('name') && empty($plan->slug)) {
                $plan->slug = \Illuminate\Support\Str::slug($plan->name);
            }
        });
    }
}
