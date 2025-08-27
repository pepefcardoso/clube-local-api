<?php

namespace App\Services\StaffUserProfile;

use App\Models\StaffUserProfile;
use Illuminate\Support\Facades\DB;

class DeleteStaffUser
{
    public function delete(StaffUserProfile $staffProfile): void
    {
        DB::transaction(function () use ($staffProfile) {
            $user = $staffProfile->user;

            if ($user) {
                $user->tokens()->delete();

                $user->delete();
            }

            $staffProfile->delete();
        });
    }
}
