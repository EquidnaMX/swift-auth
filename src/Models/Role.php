<?php

/**
 * Declares the SwiftAuth role model and relationships.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Models
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a SwiftAuth role with allowed actions.
 *
 * @property int $id_role
 * @property string $name
 * @property string|null $description
 * @property string $actions Comma-separated actions
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $users
 *
 * @method static Builder|static where($column, $operator = null, $value = null)
 * @method static static create(array $attributes = [])
 * @method static static firstOrCreate(array $attributes = [], array $values = [])
 * @method static static find($id)
 * @method static static findOrFail($id)
 * @method static Builder|static orderBy($column, $direction = 'asc')
 */
class Role extends Model
{
    protected $table = 'Roles';

    protected $primaryKey = 'id_role';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'actions',
    ];

    /**
     * The users that belong to this role.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'UsersRoles',
            'id_role',
            'id_user'
        );
    }

    /**
     * Scope a query to filter roles by name.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $query->where('name', 'LIKE', '%' . $search . '%');
    }
}
