<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login
{
    public function login(array $credentials): array
    {
        $user = User::active()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $user->updateLastLogin();

        $token = $user->generateApiToken();

        return [
            'user' => $user->load('profileable'),
            'token' => $token,
        ];
    }
}
