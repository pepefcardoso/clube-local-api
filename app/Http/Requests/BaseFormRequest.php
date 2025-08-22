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
        return false;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    protected function userRules(bool $isUpdate = false, string $table = 'users', string $routeParam = 'user'): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        $emailRule = [$rule, 'email', 'max:255'];

        if ($isUpdate) {
            $modelId = $this->route($routeParam) ? $this->route($routeParam)->id : null;
            $emailRule[] = 'unique:' . $table . ',email,' . $modelId;
        } else {
            $emailRule[] = 'unique:' . $table . ',email';
        }

        return [
            'name' => [$rule, 'string', 'max:255'],
            'email' => $emailRule,
            'password' => [
                $rule,
                'string',
                Password::min(8),
                'confirmed'
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este endereço de e-mail já está cadastrado.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não corresponde.',
            'phone.regex' => 'O formato do número de telefone é inválido.',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
        ];
    }
}
