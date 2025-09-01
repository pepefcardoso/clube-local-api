<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'country' => ['nullable', 'string', 'max:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_primary' => ['nullable', 'boolean'],
            'type' => ['required', 'string', 'in:residential,commercial,billing,shipping'],
        ];
    }

    public function messages(): array
    {
        return [
            'street.required' => 'O nome da rua é obrigatório.',
            'number.required' => 'O número é obrigatório.',
            'neighborhood.required' => 'O bairro é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',
            'state.size' => 'O estado deve ter exatamente 2 caracteres.',
            'zip_code.required' => 'O CEP é obrigatório.',
            'zip_code.regex' => 'O CEP deve estar no formato 00000-000 ou 00000000.',
            'country.size' => 'O país deve ter exatamente 2 caracteres.',
            'latitude.between' => 'A latitude deve estar entre -90 e 90.',
            'longitude.between' => 'A longitude deve estar entre -180 e 180.',
            'type.required' => 'O tipo de endereço é obrigatório.',
            'type.in' => 'O tipo deve ser: residential, commercial, billing ou shipping.',
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

        if ($this->has('country') && empty($this->country)) {
            $this->merge([
                'country' => 'BR'
            ]);
        }
    }
}
