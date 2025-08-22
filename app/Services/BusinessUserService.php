<?php

namespace App\Services;

use App\Models\BusinessUser;
use Illuminate\Http\Request;

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

    public function registerBusinessUser(array $data): array
    {
        return $this->registerUser($data);
    }

    public function getBusinessUsers(Request $request)
    {
        return $this->getUsers($request);
    }

    public function getBusinessUser(BusinessUser $businessUser)
    {
        return $this->getUser($businessUser);
    }

    public function createBusinessUser(array $data)
    {
        return $this->createUser($data);
    }

    public function updateBusinessUser(BusinessUser $businessUser, array $data)
    {
        return $this->updateUser($businessUser, $data);
    }

    public function deleteBusinessUser(BusinessUser $businessUser)
    {
        return $this->deleteUser($businessUser);
    }
}
