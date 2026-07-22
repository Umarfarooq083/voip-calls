<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'customer_name',
        'phone_number',
        'status',
        'call_uuid',
        'channel',
        'called_at',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
        'called_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCalling($query)
    {
        return $query->where('status', 'calling');
    }

    public function scopeCallingRinging($query)
    {
        return $query->where('status', 'calling_ringing');
    }

    public function scopeAttended($query)
    {
        return $query->where('status', 'attended');
    }

    public function scopePressed1($query)
    {
        return $query->where('status', '1_pressed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeNotAnswered($query)
    {
        return $query->where('status', 'not_answered');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }

    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function scopeTerminal($query)
    {
        return $query->whereIn('status', ['success', 'successful', 'rejected', 'not_answered', 'failed', 'skipped']);
    }
}
