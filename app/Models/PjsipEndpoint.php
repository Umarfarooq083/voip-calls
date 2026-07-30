<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjsipEndpoint extends Model
{
    protected $table = 'ps_endpoints';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'transport',
        'context',
        'disallow',
        'allow',
        'direct_media',
        'auth',
        'aors',
        'auth_type',
        'pinned_media',
        'description',
        'timeout',
        'send_rtp_sdp',
        'rtptimeout',
        'rtpholdtimeout',
        'rtpkeepalive',
        'media_address',
        'force_avp',
        'ice_support',
        'ice_host',
        'ice_port',
        'rtpengine',
        'rewrite_contact',
        'callerid',
        'callerid_privacy',
        'callerid_string',
        'fax_detect',
        'fax_detect_timeout',
        'cameras',
        'video_support',
        'text_support',
        'text_enable',
        'voicemail_extension',
        'outbound_proxy',
        'websocket_binary_type',
        'dtls_enable',
        'dtls_cert_file',
        'dtls_private_key_file',
        'dtls_ca_file',
        'dtls_verify_client',
        'dtls_verify_server',
        'dtls_setup',
        'dtls_fingerprint',
        'media_encryption',
        'media_encryption_optimistic',
        'media_encryption_method',
        'use_ice',
        'mobile_app',
        'sdp_owner',
        'sdp_session',
        'tos_audio',
        'tos_video',
        'encryption',
        'sub_min_sec',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function auths()
    {
        return $this->hasOne(PjsipAuth::class, 'id', 'auth');
    }

    public function aors()
    {
        return $this->hasOne(PjsipAor::class, 'id', 'aors');
    }
}
