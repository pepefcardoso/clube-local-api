<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPlanFeatures implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('O campo :attribute deve ser um array.');
            return;
        }

        foreach ($value as $index => $feature) {
            if (!is_string($feature)) {
                $fail("A feature na posição {$index} deve ser uma string.");
                return;
            }

            if (empty(trim($feature))) {
                $fail("A feature na posição {$index} não pode estar vazia.");
                return;
            }

            if (strlen($feature) > 255) {
                $fail("A feature na posição {$index} deve ter no máximo 255 caracteres.");
                return;
            }
        }

        $uniqueFeatures = array_unique($value);
        if (count($uniqueFeatures) !== count($value)) {
            $fail('O campo :attribute não pode conter features duplicadas.');
        }
    }
}
