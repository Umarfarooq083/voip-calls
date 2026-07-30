<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ps_endpoints', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('transport', 128)->nullable();
            $table->string('context', 128)->nullable();
            $table->string('disallow', 255)->nullable();
            $table->string('allow', 255)->nullable();
            $table->string('direct_media', 128)->nullable();
            $table->string('auth', 128)->nullable();
            $table->string('aors', 128)->nullable();
            $table->string('auth_type', 128)->nullable();
            $table->string('pinned_media', 128)->nullable();
            $table->string('description', 128)->nullable();
            $table->string('timeout', 128)->nullable();
            $table->string('send_rtp_sdp', 128)->nullable();
            $table->string('rtptimeout', 128)->nullable();
            $table->string('rtpholdtimeout', 128)->nullable();
            $table->string('rtpkeepalive', 128)->nullable();
            $table->string('media_address', 128)->nullable();
            $table->string('force_avp', 128)->nullable();
            $table->string('ice_support', 128)->nullable();
            $table->string('ice_host', 128)->nullable();
            $table->string('ice_port', 128)->nullable();
            $table->string('rtpengine', 128)->nullable();
            $table->string('rewrite_contact', 128)->nullable();
            $table->string('callerid', 128)->nullable();
            $table->string('callerid_privacy', 128)->nullable();
            $table->string('callerid_string', 128)->nullable();
            $table->string('fax_detect', 128)->nullable();
            $table->string('fax_detect_timeout', 128)->nullable();
            $table->string('cameras', 128)->nullable();
            $table->string('video_support', 128)->nullable();
            $table->string('text_support', 128)->nullable();
            $table->string('text_enable', 128)->nullable();
            $table->string('voicemail_extension', 128)->nullable();
            $table->string('outbound_proxy', 128)->nullable();
            $table->string('websocket_binary_type', 128)->nullable();
            $table->string('dtls_enable', 128)->nullable();
            $table->string('dtls_cert_file', 128)->nullable();
            $table->string('dtls_private_key_file', 128)->nullable();
            $table->string('dtls_ca_file', 128)->nullable();
            $table->string('dtls_verify_client', 128)->nullable();
            $table->string('dtls_verify_server', 128)->nullable();
            $table->string('dtls_setup', 128)->nullable();
            $table->string('dtls_fingerprint', 128)->nullable();
            $table->string('media_encryption', 128)->nullable();
            $table->string('media_encryption_optimistic', 128)->nullable();
            $table->string('media_encryption_method', 128)->nullable();
            $table->string('use_ice', 128)->nullable();
            $table->string('mobile_app', 128)->nullable();
            $table->string('sdp_owner', 128)->nullable();
            $table->string('sdp_session', 128)->nullable();
            $table->string('tos_audio', 128)->nullable();
            $table->string('tos_video', 128)->nullable();
            $table->string('dtlsverifyclient', 128)->nullable();
            $table->string('dtlsverifyserver', 128)->nullable();
            $table->string('dtlscertfile', 128)->nullable();
            $table->string('dtlsprivatekeyfile', 128)->nullable();
            $table->string('dtlscafile', 128)->nullable();
            $table->string('encryption', 128)->nullable();
            $table->string('sub_min_sec', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ps_endpoints');
    }
};
