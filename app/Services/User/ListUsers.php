<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListUsers
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with(['profileable']);

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['profile_type'])) {
            $profileTypeClass = match ($filters['profile_type']) {
                'customer' => \App\Models\CustomerProfile::class,
                'business' => \App\Models\BusinessUserProfile::class,
                'staff' => \App\Models\StaffUserProfile::class,
                default => null
            };

            if ($profileTypeClass) {
                $query->where('profileable_type', $profileTypeClass);
            }
        }

        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}
