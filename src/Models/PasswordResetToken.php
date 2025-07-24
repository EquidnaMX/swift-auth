<?php

namespace Teleurban\SwiftAuth\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    protected $table = 'PasswordResetTokens';
    protected $primaryKey = 'email';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];
}
