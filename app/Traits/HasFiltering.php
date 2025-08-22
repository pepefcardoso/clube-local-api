<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasFiltering
{
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $search = $request->get('q');
        $filters = $request->get('filter', []);
        $sort = $request->get('sort');
        $order = $request->get('order', 'asc');

        return $query
            ->when($search, fn(Builder $q) => $this->applyGlobalSearch($q, (string) $search))
            ->when($filters, fn(Builder $q) => $this->applyFilters(
                $q,
                is_array($filters) ? $filters : (json_decode((string) $filters, true) ?: [])
            ))
            ->when($sort, fn(Builder $q) => $this->applySort($q, (string) $sort, (string) $order));
    }

    protected function applyGlobalSearch(Builder $query, string $search): void
    {
        $searchableFields = $this->getSearchableFields();

        $query->where(function (Builder $q) use ($search, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $filterableFields = $this->getFilterableFields();

        foreach ($filters as $field => $value) {
            if (in_array($field, $filterableFields, true) && $value !== null && $value !== '') {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }
    }

    protected function applySort(Builder $query, string $field, string $order): void
    {
        $sortableFields = $this->getSortableFields();

        if (in_array($field, $sortableFields, true)) {
            $order = strtolower($order) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($field, $order);
        }
    }

    abstract protected function getSearchableFields(): array;

    abstract protected function getFilterableFields(): array;

    protected function getSortableFields(): array
    {
        return $this->getFilterableFields();
    }
}
