<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjsipContact extends Model
{
    protected $table = 'ps_contacts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'contact_uri',
        'contact_expires',
        'contact_last_attempt',
        'contact_status',
        'contact_rtt',
        'contact_transport',
        'contact_user_agent',
        'contact_ip',
        'contact_port',
        'contact_protocol',
        'contact_refresh',
        'contact_endpoint',
    ];

    protected $casts = [
        'contact_port' => 'integer',
    ];
}
