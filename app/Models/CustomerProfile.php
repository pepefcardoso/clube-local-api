<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Enums\ProfileStatus;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpf',
        'birth_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'status' => ProfileStatus::class,
        ];
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'profileable');
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_customer_profile');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProfileStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === ProfileStatus::Active;
    }
}
