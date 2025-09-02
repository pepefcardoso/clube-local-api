<?php

namespace App\Services\PlatformPlan;

use App\Models\PlatformPlan;
use Illuminate\Support\Facades\DB;

class DeletePlatformPlan
{
    public function delete(PlatformPlan $plan): void
    {
        DB::transaction(function () use ($plan) {
            if ($plan->businesses()->exists()) {
                throw new \Exception('Não é possível excluir um plano que possui negócios associados.');
            }

            $plan->delete();
        });
    }
}
