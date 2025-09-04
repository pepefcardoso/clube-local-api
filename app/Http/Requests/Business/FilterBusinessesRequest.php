<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class FilterBusinessesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', \App\Models\Business::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,active,suspended,inactive'],
            'has_plan' => ['nullable', 'boolean'],
            'platform_plan_id' => ['nullable', 'exists:platform_plans,id'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'approved' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['nullable', 'string', 'in:name,slug,status,approved_at,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'O status deve ser: pending, active, suspended ou inactive.',
            'state.size' => 'O estado deve ter exatamente 2 caracteres.',
            'sort_by.in' => 'O campo de ordenação deve ser: name, slug, status, approved_at, created_at ou updated_at.',
            'sort_direction.in' => 'A direção da ordenação deve ser: asc ou desc.',
            'per_page.min' => 'O número de itens por página deve ser no mínimo 1.',
            'per_page.max' => 'O número de itens por página deve ser no máximo 100.',
            'page.min' => 'O número da página deve ser no mínimo 1.',
            'date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('state')) {
            $this->merge([
                'state' => strtoupper($this->state)
            ]);
        }
    }
}
