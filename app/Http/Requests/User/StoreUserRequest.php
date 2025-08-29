<?php

namespace App\Http\Requests\User;

use App\Rules\ValidCPF;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'profile_type' => ['required', 'string', 'in:customer,business,staff'],
        ];

        if ($this->input('profile_type') === 'customer') {
            $rules['cpf'] = ['nullable', 'string', new ValidCPF, 'unique:customer_profiles'];
            $rules['birth_date'] = ['nullable', 'date', 'before:today'];
            $rules['status'] = ['nullable', 'string', 'in:active,inactive,suspended'];
            $rules['access_level'] = ['nullable', 'string', 'in:basic,premium,vip'];
        }

        if ($this->input('profile_type') === 'business') {
            $rules['business_id'] = ['required', 'exists:businesses,id'];
            $rules['status'] = ['nullable', 'string', 'in:active,inactive,suspended'];
            $rules['access_level'] = ['nullable', 'string', 'in:user,manager,admin'];
        }

        if ($this->input('profile_type') === 'staff') {
            $rules['access_level'] = ['nullable', 'string', 'in:basic,advanced,admin'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ter um formato válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'profile_type.required' => 'O tipo de perfil é obrigatório.',
            'business_id.required' => 'O ID do negócio é obrigatório para perfis empresariais.',
            'business_id.exists' => 'O negócio selecionado não existe.',
        ];
    }
}
