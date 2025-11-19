<?php

namespace Equidna\SwifthAuth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class User
 *
 * @property int $id_user
 * @property string $name
 * @property string $email
 * @property string $password
 */
class User extends Authenticatable
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $primaryKey = 'id_user';

    /**
     * Initialize the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $prefix = config('swift-auth.table_prefix', '');
        $this->table = $prefix . 'Users';
    }

    /**
     * @var array<int, string>
     */
    protected $with = ['roles'];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The roles associated with the user.
     *
     * @return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        $prefix = config('swift-auth.table_prefix', '');
        return $this->belongsToMany(
            Role::class,
            $prefix . 'UsersRoles',
            'id_user',
            'id_role'
        );
    }

    /**
     * Check if the user has any of the given roles (by name).
     *
     * @param string|array<string> $roles List of role names to check.
     * @return bool True if the user has at least one of the roles.
     */
    public function hasRoles(string|array $roles): bool
    {
        $rolesToCheck = collect((array) $roles)->map(fn($r) => strtolower($r));

        return $this->roles
            ->pluck('name')
            ->map(fn($name) => strtolower($name))
            ->intersect($rolesToCheck)
            ->isNotEmpty();
    }

    /**
     * Get the list of available actions from all assigned roles.
     *
     * @return array<int, string> Unique list of actions the user can perform.
     */
    public function availableActions(): array
    {
        $actions = [];

        foreach ($this->roles as $role) {
            if (empty($role->actions)) {
                continue;
            }

            $roleActions = array_filter(
                array_map(
                    static fn(string $action) => trim($action),
                    explode(',', $role->actions)
                )
            );

            $actions = array_merge($actions, $roleActions);
        }

        return array_values(array_unique($actions));
    }

    /**
     * Scope a query to filter users by name or email.
     *
     * @param Builder $query
     * @param string|null $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, null|string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', '%' . $search . '%')
                ->orWhere('email', 'LIKE', '%' . $search . '%');
        });
    }
}
