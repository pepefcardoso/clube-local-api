<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessUser\StoreBusinessUserRequest;
use App\Http\Requests\BusinessUser\UpdateBusinessUserRequest;
use App\Models\BusinessUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BusinessUserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BusinessUser::class, 'business_user');
    }

    public function index(Request $request): JsonResponse
    {
        $query = BusinessUser::query();

        if (auth()->user() instanceof BusinessUser) {
            $query->where('company_name', auth()->user()->company_name);
        }

        $businessUsers = $query
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->with('roles')
            ->paginate($request->per_page ?? 15);

        return response()->json($businessUsers);
    }

    public function store(StoreBusinessUserRequest $request): JsonResponse
    {
        $businessUser = BusinessUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        $businessUser->load('roles');

        return response()->json([
            'message' => 'Business user created successfully',
            'business_user' => $businessUser,
        ], 201);
    }

    public function show(BusinessUser $businessUser): JsonResponse
    {
        $businessUser->load('roles', 'permissions');

        return response()->json([
            'business_user' => $businessUser,
            'is_manager' => $businessUser->isManager(),
        ]);
    }

    public function update(UpdateBusinessUserRequest $request, BusinessUser $businessUser): JsonResponse
    {
        $data = $request->validated();

        if (auth()->user()->id === $businessUser->id) {
            $data = collect($data)->only([
                'name',
                'phone'
            ])->toArray();
        }

        $businessUser->update($data);
        $businessUser->load('roles');

        return response()->json([
            'message' => 'Business user updated successfully',
            'business_user' => $businessUser,
        ]);
    }

    public function destroy(BusinessUser $businessUser): JsonResponse
    {
        $businessUser->tokens()->delete();
        $businessUser->delete();

        return response()->json([
            'message' => 'Business user deleted successfully',
        ]);
    }
    public function register(StoreBusinessUserRequest $request): JsonResponse
    {
        $businessUser = BusinessUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        $token = $businessUser->createToken(
            name: 'business_token',
            abilities: ['business:read', 'business:update'],
            expiresAt: now()->addDays(30)
        );

        return response()->json([
            'message' => 'Business user registered successfully',
            'business_user' => [
                'id' => $businessUser->id,
                'name' => $businessUser->name,
                'email' => $businessUser->email,
                'type' => 'business',
                'roles' => $businessUser->getRoleNames(),
            ],
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ], 201);
    }

    public function activate(BusinessUser $businessUser): JsonResponse
    {
        $businessUser->update(['is_active' => true]);

        return response()->json([
            'message' => 'Business user activated successfully',
            'business_user' => $businessUser,
        ]);
    }

    public function deactivate(BusinessUser $businessUser): JsonResponse
    {
        $businessUser->update(['is_active' => false]);
        $businessUser->tokens()->delete();

        return response()->json([
            'message' => 'Business user deactivated successfully',
            'business_user' => $businessUser,
        ]);
    }

    public function byCompany(Request $request): JsonResponse
    {
        $companyName = $request->input('company_name');

        if (!$companyName) {
            return response()->json([
                'error' => 'Company name is required'
            ], 422);
        }

        $businessUsers = BusinessUser::where('company_name', $companyName)
            ->where('is_active', true)
            ->with('roles')
            ->get();

        return response()->json([
            'company_name' => $companyName,
            'business_users' => $businessUsers,
            'total_employees' => $businessUsers->count(),
            'managers_count' => $businessUsers->filter(fn($user) => $user->isManager())->count(),
        ]);
    }
}
