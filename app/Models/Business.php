<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function scopeActive($query)
    {
        return $query->where('status', BusinessStatus::Active);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')->where('status', BusinessStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === BusinessStatus::Active;
    }

    public function isApproved(): bool
    {
        return !is_null($this->approved_at) && $this->isActive();
    }
}
