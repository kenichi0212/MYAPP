<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class NotPastExpiryDate implements ValidationRule
{
    /**
     * 賞味期限に過去日付は登録不可とする（SPEC.md 11.）。当日は登録可。
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! strtotime($value)) {
            return;
        }

        if (Carbon::parse($value)->startOfDay()->lt(Carbon::today())) {
            $fail('賞味期限に過去の日付は登録できません。');
        }
    }
}
