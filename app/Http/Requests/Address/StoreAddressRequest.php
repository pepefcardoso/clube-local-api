<?php

namespace App\Http\Requests\Address;

use App\Enums\AddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'addressable_id' => ['required', 'integer'],
            'addressable_type' => ['required', 'string', 'in:App\Models\Business,App\Models\CustomerProfile'],
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
            'type' => ['required', new Enum(AddressType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'addressable_id.required' => 'O ID da entidade é obrigatório.',
            'addressable_type.required' => 'O tipo da entidade é obrigatório.',
            'addressable_type.in' => 'O tipo da entidade deve ser Business ou CustomerProfile.',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $addressableType = $this->input('addressable_type');
            $addressableId = $this->input('addressable_id');

            if ($addressableType && $addressableId) {
                if (!class_exists($addressableType)) {
                    $validator->errors()->add('addressable_type', 'Tipo de entidade inválido.');
                    return;
                }

                $entity = $addressableType::find($addressableId);
                if (!$entity) {
                    $validator->errors()->add('addressable_id', 'Entidade não encontrada.');
                    return;
                }

                $type = $this->input('type');
                if ($type && $entity->hasAddressOfType(AddressType::from($type))) {
                    $validator->errors()->add('type', 'Já existe um endereço deste tipo para esta entidade.');
                }
            }
        });
    }
}
