<?php

/**
 * Tracks active SwiftAuth user sessions.
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
 * Represents a persisted session for a SwiftAuth user.
 *
 * @property int    $id_session
 * @property int    $id_user
 * @property string $session_id
 * @property string $ip_address
 * @property string $user_agent
 * @property string $device_name
 * @property string $platform
 * @property string $browser
 * @property \Illuminate\Support\Carbon $last_activity
 */
class UserSession extends Model
{
    protected $table;
    protected $primaryKey = 'id_session';

    protected $fillable = [
        'id_user',
        'session_id',
        'ip_address',
        'user_agent',
        'device_name',
        'platform',
        'browser',
        'last_activity',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'last_activity' => 'datetime',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = $this->tablePrefix() . 'Sessions';
    }

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
}
