<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\StaffUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StaffUserCollection extends ResourceCollection
{
    public $collects = StaffUserResource::class;

    public function toArray(Request $request): array
    {
        return [
            'staff_users' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => $this->when($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator, [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
            ]),
        ];
    }
}
