<?php

namespace App\Services;

use App\Models\StaffUser;
use App\Models\Customer;
use App\Models\BusinessUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffUserService
{
    public function getStaffUsers(Request $request): LengthAwarePaginator
    {
        return StaffUser::query()
            ->with('roles:id,name')
            ->filter($request)
            ->when(!$request->has('sort'), fn($q) => $q->latest())
            ->paginate($request->get('per_page', 15));
    }

    public function getStaffUser(StaffUser $staffUser): StaffUser
    {
        return $staffUser->load('roles', 'permissions');
    }

    public function createStaffUser(array $data): StaffUser
    {
        $data['password'] = Hash::make($data['password']);

        $staffUser = StaffUser::create($data);

        return $staffUser->load('roles');
    }

    public function updateStaffUser(StaffUser $staffUser, array $data): StaffUser
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (Auth::user()->id === $staffUser->id) {
            $data = collect($data)->only(['name', 'phone'])->toArray();
        }

        $staffUser->update($data);

        return $staffUser->load('roles');
    }

    public function deleteStaffUser(StaffUser $staffUser): bool
    {
        $staffUser->tokens()->delete();

        return $staffUser->delete();
    }

    public function activateUser(StaffUser $staffUser): StaffUser
    {
        $staffUser->update(['is_active' => true]);

        return $staffUser;
    }

    public function deactivateUser(StaffUser $staffUser): StaffUser
    {
        $staffUser->update(['is_active' => false]);
        $staffUser->tokens()->delete();

        return $staffUser;
    }

    public function getDashboardData(): array
    {
        return [
            'statistics' => [
                'total_customers' => Customer::count(),
                'premium_customers' => Customer::where('subscription_type', 'premium')->count(),
                'active_business_users' => BusinessUser::where('is_active', true)->count(),
                'total_companies' => BusinessUser::distinct('company_name')->count('company_name'),
                'active_staff' => StaffUser::where('is_active', true)->count(),
                'recent_logins' => StaffUser::whereNotNull('last_login_at')
                    ->where('last_login_at', '>=', now()->subDays(7))
                    ->count(),
            ],
            'recent_activity' => StaffUser::whereNotNull('last_login_at')
                ->orderBy('last_login_at', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'email', 'last_login_at', 'department']),
        ];
    }
}
