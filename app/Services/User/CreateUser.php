<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\BusinessUserProfile;
use App\Models\StaffUserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUser
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ];

            $user = User::create($userData);

            if (isset($data['profile_type'])) {
                $this->createProfile($user, $data['profile_type'], $data);
            }

            return $user->load('profileable');
        });
    }

    private function createProfile(User $user, string $profileType, array $data): void
    {
        switch ($profileType) {
            case 'customer':
                $profile = CustomerProfile::create([
                    'cpf' => $data['cpf'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'access_level' => $data['access_level'] ?? 'basic',
                ]);
                break;

            case 'business':
                $profile = BusinessUserProfile::create([
                    'business_id' => $data['business_id'],
                    'status' => $data['status'] ?? 'active',
                    'access_level' => $data['access_level'] ?? 'user',
                ]);
                break;

            case 'staff':
                $profile = StaffUserProfile::create([
                    'access_level' => $data['access_level'] ?? 'basic',
                ]);
                break;
        }

        if (isset($profile)) {
            $user->update([
                'profileable_id' => $profile->id,
                'profileable_type' => get_class($profile),
            ]);
        }
    }
}
