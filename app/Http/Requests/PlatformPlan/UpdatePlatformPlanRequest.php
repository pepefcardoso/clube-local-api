<?php

namespace App\Http\Requests\PlatformPlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $platformPlan = $this->route('platformPlan');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('platform_plans')->ignore($platformPlan->id)
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'string', 'in:monthly,yearly,lifetime,free'],
            'features' => ['sometimes', 'nullable', 'array', new \App\Rules\ValidPlanFeatures],
            'max_users' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_customers' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'O nome deve ser uma string válida.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'slug.unique' => 'Este slug já está em uso.',
            'price.min' => 'O preço deve ser maior ou igual a zero.',
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
