<?php

namespace App\Http\Requests\StaffUser;

use App\Http\Requests\BaseFormRequest;
use App\Models\StaffUser;

class StoreStaffUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StaffUser::class) &&
            $this->user()->hasPermissionTo('create_staff_user');
    }

    public function rules(): array
    {
        return $this->userRules();
    }
}
