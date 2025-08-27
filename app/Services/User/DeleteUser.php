<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            $user->delete();
        });
    }
}
