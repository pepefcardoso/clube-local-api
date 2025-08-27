<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Enums\ProfileStatus;

class BusinessUserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'status',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'status' => ProfileStatus::class,
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

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
