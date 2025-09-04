<?php

namespace App\Services\Business;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

class CreateBusiness
{
    public function create(array $data): Business
    {
        return DB::transaction(function () use ($data) {
            $business = Business::create($data);
            return $business->load(['platformPlan', 'addresses']);
        });
    }
}
