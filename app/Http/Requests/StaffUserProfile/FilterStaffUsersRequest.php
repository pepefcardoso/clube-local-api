<?php

namespace App\Http\Requests\StaffUserProfile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class FilterStaffUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', \App\Models\StaffUserProfile::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'access_level' => ['nullable', 'string', 'in:basic,advanced,admin'],
            'is_active' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:access_level,created_at,user_name,user_email'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
