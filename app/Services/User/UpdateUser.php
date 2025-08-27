<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $userData = [];

            if (isset($data['name'])) {
                $userData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }

            if (isset($data['phone'])) {
                $userData['phone'] = $data['phone'];
            }

            if (isset($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            if (isset($data['is_active'])) {
                $userData['is_active'] = $data['is_active'];
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

            if ($user->profileable && isset($data['profile_data'])) {
                $this->updateProfile($user, $data['profile_data']);
            }

            return $user->load('profileable');
        });
    }

    private function updateProfile(User $user, array $profileData): void
    {
        $profile = $user->profileable;

        if ($profile instanceof \App\Models\CustomerProfile) {
            $profile->update(array_intersect_key($profileData, [
                'cpf' => null,
                'birth_date' => null,
                'status' => null,
            ]));
        } elseif ($profile instanceof \App\Models\BusinessUserProfile) {
            $profile->update(array_intersect_key($profileData, [
                'status' => null,
                'permissions' => null,
            ]));
        } elseif ($profile instanceof \App\Models\StaffUserProfile) {
            $profile->update(array_intersect_key($profileData, [
                'access_level' => null,
                'system_permissions' => null,
            ]));
        }
    }
}
