<?php

namespace App\Http\Requests\Address;

use App\Enums\AddressType;
use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class FilterAddressesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Address::class);
    }

    public function rules(): array
    {
        return [
            'addressable_id' => ['nullable', 'integer'],
            'addressable_type' => ['nullable', 'string', 'in:App\Models\Business,App\Models\CustomerProfile'],

            'search' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'type' => ['nullable', new Enum(AddressType::class)],
            'is_primary' => ['nullable', 'boolean'],
            'has_coordinates' => ['nullable', 'boolean'],

            'sort_by' => ['nullable', 'string', 'in:street,city,state,zip_code,type,is_primary,created_at,updated_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'addressable_type.in' => 'O tipo da entidade deve ser Business ou CustomerProfile.',
            'state.size' => 'O estado deve ter exatamente 2 caracteres.',
            'sort_by.in' => 'O campo de ordenação deve ser: street, city, state, zip_code, type, is_primary, created_at ou updated_at.',
            'sort_direction.in' => 'A direção da ordenação deve ser: asc ou desc.',
            'per_page.min' => 'O número de itens por página deve ser no mínimo 1.',
            'per_page.max' => 'O número de itens por página deve ser no máximo 100.',
            'page.min' => 'O número da página deve ser no mínimo 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('zip_code')) {
            $this->merge([
                'zip_code' => preg_replace('/[^0-9]/', '', $this->zip_code)
            ]);
        }

        if ($this->has('state')) {
            $this->merge([
                'state' => strtoupper($this->state)
            ]);
        }
    }
}
