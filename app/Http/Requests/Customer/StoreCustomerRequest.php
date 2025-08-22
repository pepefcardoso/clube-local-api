<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseFormRequest;
use App\Models\Customer;

class StoreCustomerRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class) &&
            $this->user()->hasPermissionTo('create_customer');
    }

    public function rules(): array
    {
        return array_merge($this->userRules(), [
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function prepareForValidation()
    {
        if ($this->birth_date) {
            $this->merge([
                'birth_date' => date('Y-m-d', strtotime($this->birth_date))
            ]);
        }
    }
}
