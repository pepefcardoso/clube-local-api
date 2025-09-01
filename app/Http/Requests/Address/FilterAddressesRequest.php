<?php

namespace App\Http\Requests\Address;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class FilterAddressesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Address::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'type' => ['nullable', 'string', 'in:residential,commercial,billing,shipping'],
            'is_primary' => ['nullable', 'boolean'],
            'has_coordinates' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:street,city,state,zip_code,type,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'state.size' => 'O estado deve ter exatamente 2 caracteres.',
            'type.in' => 'O tipo deve ser: residential, commercial, billing ou shipping.',
        ];
    }
}
