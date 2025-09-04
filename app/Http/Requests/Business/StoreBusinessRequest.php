<?php

namespace App\Http\Requests\Business;

use App\Rules\ValidCNPJ;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\Business::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:businesses,slug'],
            'cnpj' => ['nullable', 'string', new ValidCNPJ, 'unique:businesses,cnpj'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,active,suspended,inactive'],
            'platform_plan_id' => ['nullable', 'exists:platform_plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da empresa é obrigatório.',
            'name.max' => 'O nome da empresa deve ter no máximo 255 caracteres.',
            'slug.unique' => 'Este slug já está em uso.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'email.email' => 'O e-mail deve ter um formato válido.',
            'status.in' => 'O status deve ser: pending, active, suspended ou inactive.',
            'platform_plan_id.exists' => 'O plano selecionado não existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge([
                'cnpj' => preg_replace('/[^0-9]/', '', $this->cnpj)
            ]);
        }

        if ($this->has('name') && !$this->has('slug')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name)
            ]);
        }

        if (!$this->has('status')) {
            $this->merge([
                'status' => 'pending'
            ]);
        }
    }
}
