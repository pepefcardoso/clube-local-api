<?php

namespace App\Http\Requests\BusinessUser;

use App\Http\Requests\BaseFormRequest;

class UpdateBusinessUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('business_user')) &&
            $this->user()->hasPermissionTo('update_business_user');
    }

    public function rules(): array
    {
        $userRules = $this->userRules(true, 'business_users', 'business_user');

        $specificRules = [
            'is_active' => ['sometimes', 'boolean'],
        ];

        return array_merge($userRules, $specificRules);
    }
}
