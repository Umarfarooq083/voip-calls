<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('extension', 20);
            $table->string('secret')->nullable();
            $table->string('display_name')->nullable();
            $table->string('context', 128)->default('from-internal');
            $table->string('host', 64)->default('dynamic');
            $table->string('transport', 128)->nullable();
            $table->string('caller_id_name', 80)->nullable();
            $table->string('caller_id_num', 80)->nullable();
            $table->string('mailbox', 20)->nullable();
            $table->string('vm_context', 80)->default('default');
            $table->integer('timeout')->default(30);
            $table->integer('ttl')->default(3600);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('last_registered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extensions');
    }
};
