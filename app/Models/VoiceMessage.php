<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoiceMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'file_name',
        'duration',
        'mime_type',
        'size',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size' => 'integer',
    ];

    public function campaign()
    {
        return $this->hasMany(Campaign::class);
    }
}