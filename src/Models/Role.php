<?php

namespace Equidna\SwifthAuth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 *
 * @property int $id_role
 * @property string $name
 * @property string|null $description
 * @property string $actions Comma-separated actions
 */
class Role extends Model
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
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
     * @return BelongsToMany<User>
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
     * @param Builder $query
     * @param string|null $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, null|string $search): Builder
    {
        return $query->where('name', 'LIKE', '%' . $search . '%');
    }
}
