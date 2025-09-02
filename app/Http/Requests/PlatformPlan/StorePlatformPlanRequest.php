<?php

namespace App\Http\Requests\PlatformPlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlatformPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:platform_plans'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'string', 'in:monthly,yearly,lifetime,free'],
            'features' => ['nullable', 'array', new \App\Rules\ValidPlanFeatures],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_customers' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do plano é obrigatório.',
            'slug.required' => 'O slug é obrigatório.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'slug.unique' => 'Este slug já está em uso.',
            'price.required' => 'O preço é obrigatório.',
            'price.min' => 'O preço deve ser maior ou igual a zero.',
            'billing_cycle.required' => 'O ciclo de cobrança é obrigatório.',
            'billing_cycle.in' => 'O ciclo de cobrança deve ser: monthly, yearly, lifetime ou free.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => strtolower(str_replace(' ', '-', $this->slug))
            ]);
        }

        if ($this->has('billing_cycle') && $this->billing_cycle === 'free') {
            $this->merge(['price' => 0]);
        }
    }
}
