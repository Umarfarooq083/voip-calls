<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('ivr_id');
            $table->string('ivr_name')->nullable();
            $table->integer('no_of_calls');
            $table->foreignId('voice_message_id')->nullable()->constrained('voice_messages')->onDelete('set null');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'paused', 'cancelled'])->default('pending');
            $table->integer('total_contacts')->default(0);
            $table->integer('called_contacts')->default(0);
            $table->integer('successful_calls')->default(0);
            $table->integer('failed_calls')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};