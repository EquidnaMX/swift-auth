<?php

/**
 * Provides persistence for SwiftAuth password reset tokens.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Models
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a password reset token row keyed by email.
 *
 * @method static static updateOrCreate(array $attributes, array $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder|static where($column, $operator = null, $value = null)
 */
class PasswordResetToken extends Model
{
    /**
     * Table backing the password reset tokens.
     *
     * @var string
     */
    protected $table = 'PasswordResetTokens';

    /**
     * The primary key for the model (email string).
     *
     * @var string
     */
    protected $primaryKey = 'email';

    /**
     * The primary key is non-incrementing and is a string.
     *
     * @var bool
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * We manage created_at manually on the token row.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];
}
