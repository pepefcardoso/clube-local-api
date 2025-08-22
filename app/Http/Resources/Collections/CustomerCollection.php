<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    public $collects = CustomerResource::class;

    public function toArray(Request $request): array
    {
        return [
            'customers' => $this->collection,
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
