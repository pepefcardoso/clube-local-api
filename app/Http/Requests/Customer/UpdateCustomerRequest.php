<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseFormRequest;

class UpdateCustomerRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customer')) &&
            $this->user()->hasPermissionTo('update_customer');
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $userRules = $this->userRules(true, $customer->id);
        unset($userRules['password']);

        $specificRules = [
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string'],
        ];

        return array_merge($userRules, $specificRules);
    }
}
