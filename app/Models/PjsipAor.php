<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjsipAor extends Model
{
    protected $table = 'ps_aors';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'aor_contact',
        'aor_qualify_frequency',
        'aor_qualify_timeout',
        'aor_remove_existing',
        'aor_remove_unavailable',
        'aor_max_contacts',
        'aor_minimum_expire',
        'aor_max_expire',
        'aor_outbound_auth',
        'aor_may_be_available',
        'aor_support_path',
        'aor_remove_on_unavailable',
        'aor_remove_on_expire',
    ];

    protected $casts = [
        'aor_qualify_frequency' => 'integer',
        'aor_qualify_timeout' => 'integer',
        'aor_max_contacts' => 'integer',
        'aor_minimum_expire' => 'integer',
        'aor_max_expire' => 'integer',
        'aor_outbound_auth' => 'integer',
    ];
}
