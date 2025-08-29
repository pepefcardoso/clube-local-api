<?php

namespace App\Services\StaffUserProfile;

use App\Models\StaffUserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateStaffUser
{
    public function update(StaffUserProfile $staffProfile, array $data): StaffUserProfile
    {
        return DB::transaction(function () use ($staffProfile, $data) {
            $staffData = [];

            if (isset($data['access_level'])) {
                $staffData['access_level'] = $data['access_level'];
            }

            if (!empty($staffData)) {
                $staffProfile->update($staffData);
            }

            if ($staffProfile->user && !empty($data['user_data'])) {
                $userData = [];

                if (isset($data['user_data']['name'])) {
                    $userData['name'] = $data['user_data']['name'];
                }

                if (isset($data['user_data']['email'])) {
                    $userData['email'] = $data['user_data']['email'];
                }

                if (isset($data['user_data']['phone'])) {
                    $userData['phone'] = $data['user_data']['phone'];
                }

                if (isset($data['user_data']['password'])) {
                    $userData['password'] = Hash::make($data['user_data']['password']);
                }

                if (isset($data['user_data']['is_active'])) {
                    $userData['is_active'] = $data['user_data']['is_active'];
                }

                if (!empty($userData)) {
                    $staffProfile->user->update($userData);
                }
            }

            return $staffProfile->load('user');
        });
    }
}
