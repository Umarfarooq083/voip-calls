<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjsipAuth extends Model
{
    protected $table = 'ps_auths';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'auth_type',
        'auth_realm',
        'auth_username',
        'auth_password',
        'auth_md5_credential',
        'auth_nonce',
        'auth_qop',
        'auth_algorithm',
        'auth_domain',
        'auth_refresh',
        'auth_expires',
        'auth_grace_period',
        'auth_challenge',
        'auth_allow',
        'auth_blocking',
        'auth_fail_timeout',
        'contact',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
