<?php

namespace App\Services\Address;

use App\Models\Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListAddresses
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Address::query()->with('addressable');

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['addressable_id']) && !empty($filters['addressable_type'])) {
            $query->where('addressable_id', $filters['addressable_id'])
                ->where('addressable_type', $filters['addressable_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('street', 'like', "%{$search}%")
                    ->orWhere('neighborhood', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('zip_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['city'])) {
            $query->byCity($filters['city']);
        }

        if (!empty($filters['state'])) {
            $query->byState($filters['state']);
        }

        if (!empty($filters['zip_code'])) {
            $query->byZipCode($filters['zip_code']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['is_primary'])) {
            if ($filters['is_primary']) {
                $query->primary();
            } else {
                $query->where('is_primary', false);
            }
        }

        if (isset($filters['has_coordinates'])) {
            if ($filters['has_coordinates']) {
                $query->whereNotNull('latitude')->whereNotNull('longitude');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('latitude')->orWhereNull('longitude');
                });
            }
        }

        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $direction);
        } else {
            $query->orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'desc');
        }
    }
}
