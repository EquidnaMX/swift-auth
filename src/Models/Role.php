<?php

/**
 * Represents a system role.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Models
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Equidna\SwiftAuth\Models\User;

/**
 * Class Role
 *
 * @property int $id_role
 * @property string $name
 * @property string|null $description
 * @property array<int, string> $actions List of action identifiers
 *
 * @method static \Illuminate\Database\Eloquent\Builder<\Equidna\SwiftAuth\Models\Role> search(null|string $term)
 * @method static static create(array<string,mixed> $attributes = [])
 * @method static static findOrFail(string|int $id)
 * @method static static firstOrCreate(array<string,mixed> $attributes, array<string,mixed> $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder orderBy(string $column, string $direction = 'asc')
 */
class Role extends Model
{
    protected $table;
    protected $primaryKey = 'id_role';

    /**
     * Initialize the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $prefix = config('swift-auth.table_prefix', '');
        $this->table = $prefix . 'Roles';
    }

    protected $fillable = [
        'name',
        'description',
        'actions',
    ];

    protected $casts = [
        'actions' => 'array',
    ];

    /**
     * The users that belong to this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<
     *     \Equidna\SwiftAuth\Models\User,
     *     $this,
     *     \Illuminate\Database\Eloquent\Relations\Pivot,
     *     'pivot'
     * >
     */
    public function users(): BelongsToMany
    {
        $prefix = config('swift-auth.table_prefix', '');
        return $this->belongsToMany(
            User::class,
            $prefix . 'UsersRoles',
            'id_role',
            'id_user'
        );
    }

    /**
     * Scope a query to filter roles by name.
     *
     * @param \Illuminate\Database\Eloquent\Builder<\Equidna\SwiftAuth\Models\Role> $query
     * @param string|null $search
     * @return \Illuminate\Database\Eloquent\Builder<\Equidna\SwiftAuth\Models\Role>
     */
    public function scopeSearch(Builder $query, null|string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where('name', 'LIKE', '%' . $search . '%');
    }
}
