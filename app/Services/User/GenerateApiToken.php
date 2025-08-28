<?php

namespace App\Services\User;

use App\Models\User;

class GenerateApiToken
{
    public function generate(User $user): string
    {
        return $user->generateApiToken();
    }
}
