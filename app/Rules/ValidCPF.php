<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCPF implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $cpf = preg_replace('/[^0-9]/', '', $value);

        if (strlen($cpf) !== 11) {
            $fail('The :attribute must be a valid CPF.');
            return;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            $fail('The :attribute must be a valid CPF.');
            return;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                $fail('The :attribute must be a valid CPF.');
                return;
            }
        }
    }
}
