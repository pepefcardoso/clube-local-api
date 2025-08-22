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
        $staffUser = $this->route('staff_user');
        $userRules = $this->userRules(true, $staffUser->id);

        $specificRules = [
            'is_active' => ['sometimes', 'boolean'],
        ];

        return array_merge($userRules, $specificRules);
    }
}
