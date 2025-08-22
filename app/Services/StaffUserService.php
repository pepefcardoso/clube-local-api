<?php

namespace App\Services;

use App\Models\StaffUser;
use Illuminate\Http\Request;


class StaffUserService extends BaseUserService
{
    protected function getModel(): string
    {
        return StaffUser::class;
    }

    protected function getDefaultData(): array
    {
        return ['is_active' => true];
    }

    public function registerStaffUser(array $data): array
    {
        return $this->registerUser($data);
    }

    public function getStaffUsers(Request $request)
    {
        return $this->getUsers($request);
    }

    public function getStaffUser(StaffUser $staffUser)
    {
        return $this->getUser($staffUser);
    }

    public function createStaffUser(array $data)
    {
        return $this->createUser($data);
    }

    public function updateStaffUser(StaffUser $staffUser, array $data)
    {
        return $this->updateUser($staffUser, $data);
    }

    public function deleteStaffUser(StaffUser $staffUser)
    {
        return $this->deleteUser($staffUser);
    }
}
