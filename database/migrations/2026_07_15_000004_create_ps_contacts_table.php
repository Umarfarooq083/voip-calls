<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ps_contacts', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('contact_uri', 255)->nullable();
            $table->string('contact_expires', 128)->nullable();
            $table->string('contact_last_attempt', 128)->nullable();
            $table->string('contact_status', 128)->nullable();
            $table->string('contact_rtt', 128)->nullable();
            $table->string('contact_transport', 128)->nullable();
            $table->string('contact_user_agent', 255)->nullable();
            $table->string('contact_ip', 45)->nullable();
            $table->string('contact_port', 10)->nullable();
            $table->string('contact_protocol', 10)->nullable();
            $table->string('contact_refresh', 128)->nullable();
            $table->string('contact_endpoint', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ps_contacts');
    }
};
