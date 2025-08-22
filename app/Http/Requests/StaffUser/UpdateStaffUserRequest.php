<?php

namespace App\Http\Requests\StaffUser;

use App\Http\Requests\BaseFormRequest;

class UpdateStaffUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('staff_user')) &&
            $this->user()->hasPermissionTo('update_staff_user');
    }

    public function rules(): array
    {
        $userRules = $this->userRules(true, 'staff_users', 'staff_user');

        $specificRules = [
            'is_active' => ['sometimes', 'boolean'],
        ];

        return array_merge($userRules, $specificRules);
    }
}
