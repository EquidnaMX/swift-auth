<?php

namespace Teleurban\SwiftAuth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    protected $table = "Users";
    protected $primaryKey = 'id_user';
    protected $with = ["roles"];

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'UsersRoles',
            'id_user',
            'id_role'
        );
    }

    public function availableActions()
    {
        $actions = [];

        foreach ($this->roles as $role) {
            $actions = array_merge($actions, explode(",", $role->actions));
        }

        return array_unique($actions);
    }

    public function scopeSearch(Builder $query, null|string $search): Builder
    {
        return $query->where('name', 'LIKE', '%' . $search . '%');
    }
}
