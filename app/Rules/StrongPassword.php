<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Server-side mirror of the live checklist in resources/views/partials/
 * password-strength-meter.blade.php — every rule enforced here must stay
 * in lockstep with that JS (see its RULES map), so a password the meter
 * shows as fully green never gets rejected here, and vice versa. Symbols
 * are optional on both sides, same as the UI marks it.
 */
class StrongPassword implements ValidationRule
{
    /**
     * @param  string|null  $username  Checked as a case-insensitive
     *                                 substring of the password when given
     *                                 (skipped entirely when null — e.g.
     *                                 the forgot-password flow, where the
     *                                 target account isn't known yet at
     *                                 validation time).
     */
    public function __construct(private ?string $username = null) {}

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = (string) $value;

        if (mb_strlen($value) < 8) {
            $fail(__('The :attribute must be at least 8 characters.'));

            return;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail(__('The :attribute must contain at least one uppercase letter.'));

            return;
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail(__('The :attribute must contain at least one lowercase letter.'));

            return;
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail(__('The :attribute must contain at least one number.'));

            return;
        }

        if (preg_match('/(.)\1\1/u', $value)) {
            $fail(__('The :attribute must not contain 3 identical characters in a row.'));

            return;
        }

        if ($this->hasSequentialRun($value)) {
            $fail(__('The :attribute must not contain a sequential run like "abc" or "123".'));

            return;
        }

        if ($this->username !== null && mb_strlen($this->username) >= 3
            && str_contains(mb_strtolower($value), mb_strtolower($this->username))) {
            $fail(__('The :attribute must not contain your username.'));
        }
    }

    /**
     * Three or more characters in a row, ascending or descending by
     * character code — "abc"/"cba", "123"/"321", etc.
     */
    private function hasSequentialRun(string $value): bool
    {
        $chars = mb_str_split(mb_strtolower($value));

        for ($i = 0; $i < count($chars) - 2; $i++) {
            $a = mb_ord($chars[$i]);
            $b = mb_ord($chars[$i + 1]);
            $c = mb_ord($chars[$i + 2]);

            if (($b === $a + 1 && $c === $b + 1) || ($b === $a - 1 && $c === $b - 1)) {
                return true;
            }
        }

        return false;
    }
}
