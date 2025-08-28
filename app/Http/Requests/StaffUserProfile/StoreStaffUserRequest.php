<?php

namespace App\Http\Requests\StaffUserProfile;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'access_level' => ['required', 'string', 'in:basic,advanced,admin'],
            'system_permissions' => ['nullable', 'array'],
            'system_permissions.*' => ['string', 'in:admin:users:read,admin:users:create,admin:users:update,admin:users:delete,admin:staff:create,admin:staff:update,admin:staff:delete,admin:businesses:read,admin:businesses:approve,admin:system:manage,staff:dashboard:read,staff:reports:read'],
        ];
    }
}
