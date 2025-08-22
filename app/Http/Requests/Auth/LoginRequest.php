<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'user_type' => ['required', 'in:customer,business_user,staff_user'],
            'remember' => ['boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        $userTypeMapping = [
            'customer' => 'customer',
            'business' => 'business_user',
            'staff' => 'staff_user',
        ];

        if ($this->has('user_type') && isset($userTypeMapping[$this->user_type])) {
            $this->merge([
                'user_type' => $userTypeMapping[$this->user_type],
            ]);
        }
    }
}
