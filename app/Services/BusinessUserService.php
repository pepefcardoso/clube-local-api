<?php

namespace App\Services;

use App\Models\BusinessUser;

class BusinessUserService extends BaseUserService
{
    protected function getModel(): string
    {
        return BusinessUser::class;
    }

    protected function getDefaultData(): array
    {
        return ['is_active' => true];
    }

    public function toggleStatus(BusinessUser $user): BusinessUser
    {
        $user->update(['is_active' => !$user->is_active]);

        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return $user;
    }
}
