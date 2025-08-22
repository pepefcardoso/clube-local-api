<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\{JsonResponse, Request};

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
        $this->authService->logout($request->user());
        return $this->successResponse(message: 'Logout successful');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());
        return $this->successResponse(message: 'Logged out from all devices');
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refresh($request->user());
        return $this->successResponse($result, 'Token refreshed successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());
        return $this->successResponse($user);
    }
}
