<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Customer;
use App\Models\BusinessUser;
use App\Models\StaffUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $userModel = $this->getUserModel($request->user_type);

        $user = $userModel::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (method_exists($user, 'is_active') && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        if ($user instanceof StaffUser) {
            $user->updateLastLogin();
        }

        $abilities = $this->getTokenAbilities($user);
        $token = $user->createToken(
            name: $request->user_type . '_token',
            abilities: $abilities,
            expiresAt: now()->addDays(30)
        );

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $request->user_type,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $userType,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'profile' => $user->only($user->getFillable()),
            ],
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices'
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $request->user()->currentAccessToken()->delete();

        $abilities = $this->getTokenAbilities($user);
        $token = $user->createToken(
            name: $userType . '_token',
            abilities: $abilities,
            expiresAt: now()->addDays(30)
        );

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    private function getUserModel(string $userType): string
    {
        return match ($userType) {
            'customer' => Customer::class,
            'business' => BusinessUser::class,
            'staff' => StaffUser::class,
            default => throw new \InvalidArgumentException('Invalid user type')
        };
    }

    private function getUserType($user): string
    {
        return match (true) {
            $user instanceof Customer => 'customer',
            $user instanceof BusinessUser => 'business',
            $user instanceof StaffUser => 'staff',
            default => 'unknown'
        };
    }

    private function getTokenAbilities($user): array
    {
        $abilities = [];

        if ($user instanceof Customer) {
            $abilities = ['customer:read', 'customer:update'];
            if ($user->isPremium()) {
                $abilities[] = 'customer:premium';
            }
        } elseif ($user instanceof BusinessUser) {
            $abilities = ['business:read', 'business:update'];
            if ($user->isManager()) {
                $abilities[] = 'business:manage';
            }
        } elseif ($user instanceof StaffUser) {
            $abilities = ['staff:read', 'staff:update'];
            if ($user->isAdmin()) {
                $abilities[] = 'staff:admin';
                $abilities[] = 'system:manage';
            }
        }

        return $abilities;
    }
}
