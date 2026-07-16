<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ps_aors', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('aor_contact', 255)->nullable();
            $table->integer('aor_qualify_frequency')->default(60);
            $table->integer('aor_qualify_timeout')->default(20);
            $table->string('aor_remove_existing', 128)->nullable();
            $table->string('aor_remove_unavailable', 128)->nullable();
            $table->integer('aor_max_contacts')->default(1);
            $table->integer('aor_minimum_expire')->default(60);
            $table->integer('aor_max_expire')->default(14400);
            $table->integer('aor_outbound_auth')->default(0);
            $table->string('aor_may_be_available', 128)->nullable();
            $table->string('aor_support_path', 128)->nullable();
            $table->string('aor_remove_on_unavailable', 128)->nullable();
            $table->string('aor_remove_on_expire', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ps_aors');
    }
};
