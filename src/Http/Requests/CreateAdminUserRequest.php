<?php

/**
 * Validates administrative user creation.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Requests
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Http\Requests;

use Equidna\SwiftAuth\Support\Facades\SwiftAuth;
use Illuminate\Foundation\Http\FormRequest;
use Equidna\SwiftAuth\Classes\Auth\Services\PasswordPolicy;

/**
 * Validates registration payload with email uniqueness and password strength.
 */
final class CreateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SwiftAuth::canPerformAction('sw-admin');
    }

    /** @return array<string, array<int, mixed>|string> */
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

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least :min characters.',
        ];
    }
}
