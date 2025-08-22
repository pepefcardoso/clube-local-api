<?php

namespace App\Http\Requests\BusinessUser;

use App\Http\Requests\BaseFormRequest;
use App\Models\BusinessUser;

class StoreBusinessUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BusinessUser::class) &&
            $this->user()->hasPermissionTo('create_business_user');
    }

    public function rules(): array
    {
        return $this->userRules();
    }
}
