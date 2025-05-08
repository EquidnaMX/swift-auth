<?php

namespace Teleurban\SwiftAuth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'Roles';
    protected $primary_key = 'id_role';

    protected $fillable = [
        'name',
        'description',
        'actions'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'UsersRoles',
            'id_role',
            'id_user'
        );
    }

    public function scopeSearch(Builder $query, null|string $search): Builder
    {
        return $query->where('name', 'LIKE', '%' . $search . '%');
    }
}
