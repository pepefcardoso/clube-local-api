<?php

namespace App\Services\PlatformPlan;

use App\Models\PlatformPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdatePlatformPlan
{
    public function update(PlatformPlan $plan, array $data): PlatformPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $plan->update($data);

            return $plan;
        });
    }
}
