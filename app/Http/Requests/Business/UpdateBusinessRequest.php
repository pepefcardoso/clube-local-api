<?php

namespace App\Http\Requests\Business;

use App\Rules\ValidCNPJ;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('business'));
    }

    public function rules(): array
    {
        $business = $this->route('business');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('businesses')->ignore($business->id)],
            'cnpj' => ['sometimes', 'nullable', 'string', new ValidCNPJ, Rule::unique('businesses')->ignore($business->id)],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:pending,active,suspended,inactive'],
            'platform_plan_id' => ['sometimes', 'nullable', 'exists:platform_plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
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

        if ($this->has('name') && (!$this->has('slug') || empty($this->slug))) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name)
            ]);
        }
    }
}
