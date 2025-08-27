<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\Login;
use App\Services\Auth\Logout;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
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

            return response()->json([
                'message' => 'Login bem-sucedido',
                'user' => $result['user'],
                'token' => $result['token'],
                'abilities' => $result['abilities'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Credenciais inválidas',
                'errors' => $e->errors()
            ], 422);
        }
    }


    public function logout(Request $request): JsonResponse
    {
        $this->logoutService->logout(Auth::user());

        return response()->json([
            'message' => 'Logout com sucesso'
        ]);
    }

    // public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    // {
    //     Password::sendResetLink($request->validated());

    //     return response()->json([
    //         'message' => 'Se o e-mail fornecido estiver em nossa base de dados, um link para redefinição de senha será enviado.'
    //     ]);
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
    //         ? response()->json(['message' => __($status)])
    //         : response()->json(['message' => __($status)], 400);
    // }
}
