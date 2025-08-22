<?php

namespace App\Services;

use App\Models\BusinessUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class BusinessUserService
{
    public function getBusinessUsers(Request $request): LengthAwarePaginator
    {
        $query = BusinessUser::query()->with('roles:id,name');

        if (Auth::user() instanceof BusinessUser) {
            $query->where('company_name', Auth::user()->company_name);
        }

        return $query
            ->filter($request)
            ->when(!$request->has('sort'), fn($q) => $q->latest())
            ->paginate($request->get('per_page', 15));
    }

    public function getBusinessUser(BusinessUser $businessUser): BusinessUser
    {
        return $businessUser->load('roles', 'permissions');
    }

    public function createBusinessUser(array $data): BusinessUser
    {
        $data['password'] = Hash::make($data['password']);

        $businessUser = BusinessUser::create($data);

        return $businessUser->load('roles');
    }

    public function updateBusinessUser(BusinessUser $businessUser, array $data): BusinessUser
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (Auth::user()->id === $businessUser->id) {
            $data = collect($data)->only(['name', 'phone'])->toArray();
        }

        $businessUser->update($data);

        return $businessUser->load('roles');
    }

    public function deleteBusinessUser(BusinessUser $businessUser): bool
    {
        $businessUser->tokens()->delete();

        return $businessUser->delete();
    }

    public function activateUser(BusinessUser $businessUser): BusinessUser
    {
        $businessUser->update(['is_active' => true]);

        return $businessUser;
    }

    public function deactivateUser(BusinessUser $businessUser): BusinessUser
    {
        $businessUser->update(['is_active' => false]);
        $businessUser->tokens()->delete();

        return $businessUser;
    }

    public function registerBusinessUser(array $data): array
    {
        $data['password'] = Hash::make($data['password']);

        $businessUser = BusinessUser::create($data);

        $token = $businessUser->createToken(
            name: 'business_token',
            abilities: ['business:read', 'business:update'],
            expiresAt: now()->addDays(30)
        );

        return [
            'business_user' => $businessUser->load('roles'),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }

    public function getByCompany(string $companyName): array
    {
        $businessUsers = BusinessUser::where('company_name', $companyName)
            ->where('is_active', true)
            ->with('roles')
            ->get();

        return [
            'company_name' => $companyName,
            'business_users' => $businessUsers,
            'total_employees' => $businessUsers->count(),
            'managers_count' => $businessUsers->filter(fn($user) => $user->isManager())->count(),
        ];
    }
}
