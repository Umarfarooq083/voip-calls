<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ps_auths', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('auth_type', 128)->nullable();
            $table->string('auth_realm', 128)->nullable();
            $table->string('auth_username', 128)->nullable();
            $table->string('auth_password', 128)->nullable();
            $table->string('auth_md5_credential', 128)->nullable();
            $table->string('auth_nonce', 128)->nullable();
            $table->string('auth_qop', 128)->nullable();
            $table->string('auth_algorithm', 128)->nullable();
            $table->string('auth_domain', 128)->nullable();
            $table->string('auth_refresh', 128)->nullable();
            $table->string('auth_expires', 128)->nullable();
            $table->string('auth_grace_period', 128)->nullable();
            $table->string('auth_challenge', 128)->nullable();
            $table->string('auth_allow', 128)->nullable();
            $table->string('auth_blocking', 128)->nullable();
            $table->string('auth_fail_timeout', 128)->nullable();
            $table->string('contact', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ps_auths');
    }
};
