<?php

namespace App\Services\StaffUserProfile;

use App\Models\StaffUserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListStaffUsers
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = StaffUserProfile::query()->with(['user']);

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['access_level'])) {
            $query->where('access_level', $filters['access_level']);
        }

        if (isset($filters['is_active'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            });
        }

        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';

            if ($filters['sort_by'] === 'user_name' || $filters['sort_by'] === 'user_email') {
                $userField = str_replace('user_', '', $filters['sort_by']);
                $query->join('users', 'staff_user_profiles.id', '=', 'users.profileable_id')
                    ->where('users.profileable_type', StaffUserProfile::class)
                    ->orderBy("users.{$userField}", $direction)
                    ->select('staff_user_profiles.*');
            } else {
                $query->orderBy($filters['sort_by'], $direction);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}
