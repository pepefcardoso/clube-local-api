<?php

namespace App\Services\Auth;

use App\Models\User;

class Logout
{
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
