<?php

namespace App\Http\Requests\User;

use App\Rules\ValidCPF;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('user'));
    }

    public function rules(): array
    {
        $user = $this->route('user');

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($user->isCustomer()) {
            $rules['profile_data.cpf'] = [
                'sometimes', 'nullable', 'string', new ValidCPF,
                Rule::unique('customer_profiles', 'cpf')->ignore($user->profileable->id)
            ];
            $rules['profile_data.birth_date'] = ['sometimes', 'nullable', 'date', 'before:today'];
            $rules['profile_data.status'] = ['sometimes', 'string', 'in:active,inactive,suspended'];
        }

        if ($user->isBusinessUser()) {
            $rules['profile_data.status'] = ['sometimes', 'string', 'in:active,inactive,suspended'];
            $rules['profile_data.permissions'] = ['sometimes', 'array'];
            $rules['profile_data.permissions.*'] = ['string'];
        }

        if ($user->isStaff()) {
            $rules['profile_data.access_level'] = ['sometimes', 'string', 'in:basic,advanced,admin'];
            $rules['profile_data.system_permissions'] = ['sometimes', 'array'];
            $rules['profile_data.system_permissions.*'] = ['string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.string' => 'O nome deve ser uma string válida.',
            'email.email' => 'O e-mail deve ter um formato válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
