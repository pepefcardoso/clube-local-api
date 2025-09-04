<?php

namespace App\Services\Business;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListBusinesses
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Business::query()->with(['platformPlan', 'approvedBy', 'primaryAddress']);

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
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['has_plan'])) {
            if ($filters['has_plan']) {
                $query->whereNotNull('platform_plan_id');
            } else {
                $query->whereNull('platform_plan_id');
            }
        }

        if (!empty($filters['platform_plan_id'])) {
            $query->where('platform_plan_id', $filters['platform_plan_id']);
        }

        if (isset($filters['approved'])) {
            if ($filters['approved']) {
                $query->whereNotNull('approved_at');
            } else {
                $query->whereNull('approved_at');
            }
        }

        if (!empty($filters['city']) || !empty($filters['state'])) {
            $query->whereHas('addresses', function ($q) use ($filters) {
                if (!empty($filters['city'])) {
                    $q->where('city', 'like', "%{$filters['city']}%");
                }
                if (!empty($filters['state'])) {
                    $q->where('state', $filters['state']);
                }
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}
