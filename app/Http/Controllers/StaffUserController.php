<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffUser\StoreStaffUserRequest;
use App\Http\Requests\StaffUser\UpdateStaffUserRequest;
use App\Models\StaffUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffUserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StaffUser::class, 'staff_user');
    }

    public function index(Request $request): JsonResponse
    {
        $staffUsers = StaffUser::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->with('roles')
            ->paginate($request->per_page ?? 15);

        return response()->json($staffUsers);
    }

    public function store(StoreStaffUserRequest $request): JsonResponse
    {
        $staffUser = StaffUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        $staffUser->load('roles');

        return response()->json([
            'message' => 'Staff user created successfully',
            'staff_user' => $staffUser,
        ], 201);
    }

    public function show(StaffUser $staffUser): JsonResponse
    {
        $staffUser->load('roles', 'permissions');

        return response()->json([
            'staff_user' => $staffUser,
            'is_admin' => $staffUser->isAdmin(),
        ]);
    }

    public function update(UpdateStaffUserRequest $request, StaffUser $staffUser): JsonResponse
    {
        $data = $request->validated();

        if (auth()->user()->id === $staffUser->id) {
            $data = collect($data)->only([
                'name',
                'phone'
            ])->toArray();
        }

        $staffUser->update($data);
        $staffUser->load('roles');

        return response()->json([
            'message' => 'Staff user updated successfully',
            'staff_user' => $staffUser,
        ]);
    }

    public function destroy(StaffUser $staffUser): JsonResponse
    {
        $staffUser->tokens()->delete();
        $staffUser->delete();

        return response()->json([
            'message' => 'Staff user deleted successfully',
        ]);
    }

    public function activate(StaffUser $staffUser): JsonResponse
    {
        $this->authorize('update', $staffUser);

        $staffUser->update(['is_active' => true]);

        return response()->json([
            'message' => 'Staff user activated successfully',
            'staff_user' => $staffUser,
        ]);
    }

    public function deactivate(StaffUser $staffUser): JsonResponse
    {
        $this->authorize('update', $staffUser);

        $staffUser->update(['is_active' => false]);
        $staffUser->tokens()->delete();

        return response()->json([
            'message' => 'Staff user deactivated successfully',
            'staff_user' => $staffUser,
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $this->authorize('manageSystem', auth()->user());

        $stats = [
            'total_customers' => \App\Models\Customer::count(),
            'premium_customers' => \App\Models\Customer::where('subscription_type', 'premium')->count(),
            'active_business_users' => \App\Models\BusinessUser::where('is_active', true)->count(),
            'total_companies' => \App\Models\BusinessUser::distinct('company_name')->count(),
            'active_staff' => StaffUser::where('is_active', true)->count(),
            'recent_logins' => StaffUser::whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->subDays(7))
                ->count(),
        ];

        $recentActivity = StaffUser::whereNotNull('last_login_at')
            ->orderBy('last_login_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'last_login_at', 'department']);

        return response()->json([
            'statistics' => $stats,
            'recent_activity' => $recentActivity,
        ]);
    }
}
