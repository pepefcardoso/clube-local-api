<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class FilterUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', \App\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'profile_type' => ['nullable', 'string', 'in:customer,business,staff'],
            'sort_by' => ['nullable', 'string', 'in:name,email,created_at,last_login_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
