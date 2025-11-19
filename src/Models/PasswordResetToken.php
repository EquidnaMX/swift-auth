<?php

namespace Equidna\SwifthAuth\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    /**
     * Table backing the password reset tokens.
     *
     * @var string
     */
    protected $table;

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
     * Initialize the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $prefix = config('swift-auth.table_prefix', '');
        $this->table = $prefix . 'PasswordResetTokens';
    }

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
