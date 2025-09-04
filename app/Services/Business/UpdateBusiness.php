<?php

namespace App\Services\Business;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

class UpdateBusiness
{
    public function update(Business $business, array $data): Business
    {
        return DB::transaction(function () use ($business, $data) {
            $business->update($data);
            return $business->load(['platformPlan', 'addresses', 'businessUserProfiles.user']);
        });
    }
}
