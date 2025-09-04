<?php

namespace App\Services\Business;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

class DeleteBusiness
{
    public function delete(Business $business): void
    {
        DB::transaction(function () use ($business) {
            foreach ($business->businessUserProfiles as $profile) {
                if ($profile->user) {
                    $profile->user->tokens()->delete();
                    $profile->user->delete();
                }
                $profile->delete();
            }

            $business->customers()->detach();

            $business->addresses()->delete();

            $business->delete();
        });
    }
}
