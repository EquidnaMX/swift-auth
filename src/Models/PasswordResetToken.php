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
 * @method static static updateOrCreate(array<string,mixed> $attributes, array<string,mixed> $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder<\Equidna\SwiftAuth\Models\PasswordResetToken>|static where(string $column, mixed $operator = null, mixed $value = null)
 */
class PasswordResetToken extends Model
{
    protected $table;
    protected $primaryKey = 'email';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Initialize the model.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = $this->tablePrefix() . 'PasswordResetTokens';
        // Ensure a created_at attribute exists for new instances even when
        // Eloquent timestamps are disabled. Use a DB-friendly datetime
        // string so tests can assert presence without depending on the
        // framework helpers.
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        }
    }

    public $timestamps = false;

    /**
     * Returns configured table prefix.
     */
    protected function tablePrefix(): string
    {
        try {
            return (string) config('swift-auth.table_prefix', '');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'email',
        'token',
    ];
}
