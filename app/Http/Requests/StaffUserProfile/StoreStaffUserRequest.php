<?php

namespace App\Http\Requests\StaffUserProfile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StoreStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user &&
            $user->isStaff() &&
            $user->profileable->access_level === 'admin' &&
            Gate::allows('create', \App\Models\StaffUserProfile::class);
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
            'access_level.required' => 'O nível de acesso é obrigatório.',
            'access_level.in' => 'O nível de acesso deve ser: basic, advanced ou admin.',
        ];
    }
}
