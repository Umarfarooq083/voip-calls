<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'extension_id',
        'voice_message_id',
        'status',
        'total_contacts',
        'called_contacts',
        'successful_calls',
        'failed_calls',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => 'string',
        'total_contacts' => 'integer',
        'called_contacts' => 'integer',
        'successful_calls' => 'integer',
        'failed_calls' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function extension()
    {
        return $this->belongsTo(Extension::class);
    }

    public function voiceMessage()
    {
        return $this->belongsTo(VoiceMessage::class);
    }

    public function contacts()
    {
        return $this->hasMany(CampaignContact::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}