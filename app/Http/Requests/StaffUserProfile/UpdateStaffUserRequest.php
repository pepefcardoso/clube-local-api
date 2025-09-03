<?php

namespace App\Http\Requests\StaffUserProfile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staffProfile = $this->route('staffUserProfile');
        $user = Auth::user();

        return ($user &&
            $user->isStaff() &&
            $user->profileable->isAdmin() &&
            Gate::allows('update', $staffProfile)) ||
            ($staffProfile->user && $staffProfile->user->id === $user->id);
    }

    public function rules(): array
    {
        $staffProfile = $this->route('staffUserProfile');
        $user = $staffProfile->user;

        $rules = [
            'access_level' => ['sometimes', 'string', 'in:basic,advanced,admin'],
        ];

        if ($user) {
            $rules['user_data.name'] = ['sometimes', 'string', 'max:255'];
            $rules['user_data.email'] = [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ];
            $rules['user_data.password'] = ['sometimes', 'string', 'min:8', 'confirmed'];
            $rules['user_data.phone'] = ['sometimes', 'nullable', 'string', 'max:20'];
            $rules['user_data.is_active'] = ['sometimes', 'boolean'];
        }

        $currentUser = Auth::user();
        if ($currentUser && $staffProfile->user && $currentUser->id === $staffProfile->user->id) {
            unset($rules['user_data.is_active']);
            if ($staffProfile->isAdmin()) {
                unset($rules['access_level']);
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'access_level.in' => 'O nível de acesso deve ser: basic, advanced ou admin.',
            'user_data.name.string' => 'O nome deve ser uma string válida.',
            'user_data.email.email' => 'O e-mail deve ter um formato válido.',
            'user_data.email.unique' => 'Este e-mail já está em uso.',
            'user_data.password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'user_data.password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
