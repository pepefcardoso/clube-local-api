<?php

namespace App\Http\Requests\Address;

use App\Enums\AddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'street' => ['sometimes', 'string', 'max:255'],
            'number' => ['sometimes', 'string', 'max:20'],
            'complement' => ['sometimes', 'nullable', 'string', 'max:100'],
            'neighborhood' => ['sometimes', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'size:2'],
            'zip_code' => ['sometimes', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'country' => ['sometimes', 'string', 'max:2'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'is_primary' => ['sometimes', 'boolean'],
            'type' => ['sometimes', new Enum(AddressType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'street.string' => 'O nome da rua deve ser uma string válida.',
            'number.string' => 'O número deve ser uma string válida.',
            'neighborhood.string' => 'O bairro deve ser uma string válida.',
            'city.string' => 'A cidade deve ser uma string válida.',
            'state.size' => 'O estado deve ter exatamente 2 caracteres.',
            'zip_code.regex' => 'O CEP deve estar no formato 00000-000 ou 00000000.',
            'country.size' => 'O país deve ter exatamente 2 caracteres.',
            'latitude.between' => 'A latitude deve estar entre -90 e 90.',
            'longitude.between' => 'A longitude deve estar entre -180 e 180.',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $address = $this->route('address');
            $newType = $this->input('type');

            if ($newType && $address->type->value !== $newType) {
                $addressableId = $address->addressable_id;
                $addressableType = $address->addressable_type;

                if ($addressableId && $addressableType) {
                    $existingAddress = \App\Models\Address::where('addressable_id', $addressableId)
                        ->where('addressable_type', $addressableType)
                        ->where('type', $newType)
                        ->where('id', '!=', $address->id)
                        ->exists();

                    if ($existingAddress) {
                        $validator->errors()->add('type', 'Já existe um endereço deste tipo para esta entidade.');
                    }
                }
            }
        });
    }
}
