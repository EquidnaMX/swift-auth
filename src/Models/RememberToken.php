<?php

/**
 * Persistent remember-me tokens for SwiftAuth logins.
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
 * Represents a persistent remember-me token row.
 *
 * @property int    $id_remember_token
 * @property int    $id_user
 * @property string $selector
 * @property string $hashed_token
 * @property string $ip_address
 * @property string $user_agent
 * @property string $device_name
 * @property string $platform
 * @property string $browser
 */
class RememberToken extends Model
{
    protected $table;
    protected $primaryKey = 'id_remember_token';

    protected $fillable = [
        'id_user',
        'selector',
        'hashed_token',
        'expires_at',
        'last_used_at',
        'ip_address',
        'user_agent',
        'device_name',
        'platform',
        'browser',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        try {
            $prefix = (string) config('swift-auth.table_prefix', '');
        } catch (\Throwable $exception) {
            $prefix = '';
        }

        $this->table = $prefix . 'RememberTokens';
    }
}
