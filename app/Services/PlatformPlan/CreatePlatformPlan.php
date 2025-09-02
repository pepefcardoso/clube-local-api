<?php

namespace App\Services\PlatformPlan;

use App\Models\PlatformPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePlatformPlan
{
    public function create(array $data): PlatformPlan
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $plan = PlatformPlan::create($data);

            return $plan;
        });
    }
}
