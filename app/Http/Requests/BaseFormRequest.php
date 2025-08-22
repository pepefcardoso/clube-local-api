<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Password;

abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    protected function userRules(bool $isUpdate = false, ?string $excludeId = null): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';
        $table = $this->getTableName();

        $emailRule = [$rule, 'email', 'max:255'];
        if ($excludeId) {
            $emailRule[] = "unique:{$table},email,{$excludeId}";
        } elseif (!$isUpdate) {
            $emailRule[] = "unique:{$table},email";
        }

        return [
            'name' => [$rule, 'string', 'max:255'],
            'email' => $emailRule,
            'password' => [
                $rule,
                'string',
                Password::min(8)->mixedCase()->numbers(),
                'confirmed'
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]+$/'],
        ];
    }

    protected function getTableName(): string
    {
        return match (static::class) {
            \App\Http\Requests\Customer\StoreCustomerRequest::class,
            \App\Http\Requests\Customer\UpdateCustomerRequest::class => 'customers',
            \App\Http\Requests\BusinessUser\StoreBusinessUserRequest::class,
            \App\Http\Requests\BusinessUser\UpdateBusinessUserRequest::class => 'business_users',
            \App\Http\Requests\StaffUser\StoreStaffUserRequest::class,
            \App\Http\Requests\StaffUser\UpdateStaffUserRequest::class => 'staff_users',
            default => 'users',
        };
    }
}
