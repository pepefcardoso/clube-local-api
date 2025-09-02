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
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isFree(): bool
    {
        return $this->price == 0 || $this->billing_cycle === 'free';
    }

    public function isMonthly(): bool
    {
        return $this->billing_cycle === 'monthly';
    }

    public function isYearly(): bool
    {
        return $this->billing_cycle === 'yearly';
    }

    public function isLifetime(): bool
    {
        return $this->billing_cycle === 'lifetime';
    }

    public function hasUnlimitedUsers(): bool
    {
        return is_null($this->max_users);
    }

    public function hasUnlimitedCustomers(): bool
    {
        return is_null($this->max_customers);
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Grátis';
        }

        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    public function getBillingCycleTextAttribute(): string
    {
        return match ($this->billing_cycle) {
            'monthly' => 'Mensal',
            'yearly' => 'Anual',
            'lifetime' => 'Vitalício',
            'free' => 'Gratuito',
            default => $this->billing_cycle,
        };
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
