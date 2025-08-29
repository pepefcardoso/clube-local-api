<?php

namespace App\Http\Controllers;

use App\Services\Auth\Login;
use App\Services\Auth\Logout;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends BaseController
{
    public function __construct(
        private Login $loginService,
        private Logout $logoutService
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginService->login($request->validated());

            return $this->successResponse([
                'user' => $result['user'],
                'token' => $result['token'],
            ], 'Login bem-sucedido');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Credenciais inválidas');
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logoutService->logout(Auth::user());

        return $this->successResponse(null, 'Logout com sucesso');
    }

    // public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    // {
    //     Password::sendResetLink($request->validated());

    //     return $this->successResponse(null, 'Se o e-mail fornecido estiver em nossa base de dados, um link para redefinição de senha será enviado.');
    // }

    // public function resetPassword(ResetPasswordRequest $request): JsonResponse
    // {
    //     $status = Password::reset(
    //         $request->validated(),
    //         function ($user, $password) {
    //             $user->password = $password;
    //             $user->save();
    //         }
    //     );

    //     return $status === Password::PASSWORD_RESET
    //         ? $this->successResponse(null, __($status))
    //         : $this->errorResponse(__($status), 400);
    // }
}
