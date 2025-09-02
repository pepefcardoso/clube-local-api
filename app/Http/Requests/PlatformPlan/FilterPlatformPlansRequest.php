<?php

namespace App\Http\Requests\PlatformPlan;

use App\Models\PlatformPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class FilterPlatformPlansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', PlatformPlan::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,yearly,lifetime,free'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:name,price,billing_cycle,sort_order,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
