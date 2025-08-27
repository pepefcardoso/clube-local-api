<?php

namespace App\Services\StaffUserProfile;

use App\Models\StaffUserProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateStaffUser
{
    public function create(array $data): StaffUserProfile
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $staffProfile = StaffUserProfile::create([
                'access_level' => $data['access_level'] ?? 'basic',
                'system_permissions' => $data['system_permissions'] ?? [],
            ]);

            $user->update([
                'profileable_id' => $staffProfile->id,
                'profileable_type' => StaffUserProfile::class,
            ]);

            return $staffProfile->load('user');
        });
    }
}
