<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\{JsonResponse, Request};
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends BaseApiController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->input('email'),
            password: $request->input('password'),
            userType: $request->input('user_type'),
            remember: $request->input('remember', false)
        );

        return $this->successResponse($result, 'Login successful');
    }

    public function register(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'user_type' => 'required|in:customer,business_user,staff_user',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email|unique:business_users,email|unique:staff_users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        $userType = UserType::from($validatedData['user_type']);
        $result = $this->authService->register($validatedData, $userType);

        return $this->successResponse($result, 'Registration successful', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        // Verify that the user is actually authenticated before logging out
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->authService->logout($request->user());
        return $this->successResponse(message: 'Logout successful');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        // Verify that the user is actually authenticated before logging out
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $this->authService->logoutAll($request->user());
        return $this->successResponse(message: 'Logged out from all devices');
    }

    public function refresh(Request $request): JsonResponse
    {
        // Verify that the user is actually authenticated before refreshing
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $result = $this->authService->refresh($request->user());
        return $this->successResponse($result, 'Token refreshed successfully');
    }

    public function me(Request $request): JsonResponse
    {
        // Additional validation to ensure token still exists in database
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Verify that the current token still exists in the database
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $tokenParts = explode('|', $bearerToken);
            if (count($tokenParts) === 2) {
                [$id, $token] = $tokenParts;
                $hashedToken = hash('sha256', $token);

                $tokenExists = PersonalAccessToken::where('id', $id)
                    ->where('token', $hashedToken)
                    ->where('tokenable_type', get_class($user))
                    ->where('tokenable_id', $user->id)
                    ->exists();

                if (!$tokenExists) {
                    return response()->json(['message' => 'Token has been invalidated'], 401);
                }
            }
        }

        $userData = $this->authService->me($user);
        return $this->successResponse($userData);
    }
}
