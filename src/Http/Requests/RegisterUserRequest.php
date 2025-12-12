<?php

/**
 * Form request for user registration validation.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Requests
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Http\Requests;

use Equidna\Toolkit\Http\Requests\EquidnaFormRequest;
use Equidna\SwiftAuth\Classes\Auth\Services\PasswordPolicy;

/**
 * Validates registration payload with email uniqueness and password strength.
 */
final class RegisterUserRequest extends EquidnaFormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     *
     * Registration is public, so always return true when registration is enabled.
     *
     * @return bool  True if registration is allowed.
     */
    public function authorize(): bool
    {
        return config('swift-auth.allow_registration', true);
    }

    /**
     * Returns validation rules for registration.
     *
     * @return array<string, mixed>  Laravel validation rules.
     */
    public function rules(): array
    {
        $prefix = config('swift-auth.table_prefix', '');
        $passwordRules = PasswordPolicy::rules();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:' . $prefix . 'Users,email',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                ...$passwordRules,
            ],
            'role' => [
                'sometimes',
                'integer',
                'exists:' . $prefix . 'Roles,id_role',
            ],
        ];
    }

    /**
     * Returns custom error messages for validation failures.
     *
     * @return array<string, string>  Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least :min characters.',
        ];
    }
}
