<?php

/**
 * Form request for login validation.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Requests
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Http\Requests;

use Equidna\Toolkit\Http\Requests\EquidnaFormRequest;

/**
 * Validates login credentials (email and password) for SwiftAuth.
 */
final class LoginRequest extends EquidnaFormRequest
{
    /**
     * Authorization always allowed for login attempts.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for login.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $min = (int) config('swift-auth.password_min_length', 8);

        return [
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
                'min:' . $min,
            ],
            'remember' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
