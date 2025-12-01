<?php

/**
 * Form request for user update validation.
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
 * Validates user update payload with optional name and roles.
 */
final class UpdateUserRequest extends EquidnaFormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     *
     * Requires sw-admin action permission.
     *
     * @return bool  True if user has sw-admin permission.
     */
    public function authorize(): bool
    {
        return app('swift-auth')->canPerformAction('sw-admin');
    }

    /**
     * Returns validation rules for user updates.
     *
     * @return array<string, mixed>  Laravel validation rules.
     */
    public function rules(): array
    {
        $prefix = config('swift-auth.table_prefix', '');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'roles' => [
                'sometimes',
                'array',
            ],
            'roles.*' => [
                'integer',
                'exists:' . $prefix . 'Roles,id_role',
            ],
            'role' => [
                'sometimes',
                'integer',
                'exists:' . $prefix . 'Roles,id_role',
            ],
        ];
    }
}
