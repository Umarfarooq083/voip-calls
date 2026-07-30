<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Extension extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'extension',
        'secret',
        'display_name',
        'context',
        'host',
        'transport',
        'caller_id_name',
        'caller_id_num',
        'mailbox',
        'vm_context',
        'timeout',
        'ttl',
        'is_active',
        'notes',
        'last_registered_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_registered_at' => 'datetime',
        'timeout' => 'integer',
        'ttl' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($extension) {
            $extension->last_registered_at = now();
        });
    }

    public function pjsipEndpoint()
    {
        return $this->hasOne(PjsipEndpoint::class, 'id', 'extension');
    }

    public function pjsipAuth()
    {
        return $this->hasOne(PjsipAuth::class, 'id', 'extension');
    }

    public function pjsipAor()
    {
        return $this->hasOne(PjsipAor::class, 'id', 'extension');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
