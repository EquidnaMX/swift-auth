<?php

/**
 * Builds password validation rules from swift-auth configuration.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Services;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Generates reusable password validation rules honoring configured requirements.
 */
final class PasswordPolicy
{
    /**
     * Returns password validation rules based on configuration.
     *
     * @return array<int, \Illuminate\Validation\Rules\Password|\Illuminate\Validation\Rules\In>
     */
    public static function rules(): array
    {
        $minLength = (int) config('swift-auth.password_min_length', 8);
        /** @var array{require_letters?:bool,require_mixed_case?:bool,require_numbers?:bool,require_symbols?:bool,disallow_common_passwords?:bool,common_passwords?:array<int,string>}|null $requirements */
        $requirements = config('swift-auth.password_requirements', []);

        $rule = Password::min($minLength);

        if ($requirements['require_letters'] ?? false) {
            $rule->letters();
        }

        if ($requirements['require_mixed_case'] ?? false) {
            $rule->mixedCase();
        }

        if ($requirements['require_numbers'] ?? false) {
            $rule->numbers();
        }

        if ($requirements['require_symbols'] ?? false) {
            $rule->symbols();
        }

        $rules = [$rule];

        if ($requirements['disallow_common_passwords'] ?? false) {
            $common = $requirements['common_passwords'] ?? [];
            if (is_array($common) && $common !== []) {
                $rules[] = Rule::notIn($common);
            }
        }

        return $rules;
    }
}
