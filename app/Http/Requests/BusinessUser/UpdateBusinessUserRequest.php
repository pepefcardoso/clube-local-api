<?php

namespace App\Http\Requests\BusinessUser;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:business_users,email,' . $this->route('business_user')],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
