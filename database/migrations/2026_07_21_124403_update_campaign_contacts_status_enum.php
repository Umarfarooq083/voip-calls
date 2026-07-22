<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE campaign_contacts MODIFY COLUMN status ENUM('pending', 'calling', 'called', 'calling_ringing', 'rejected', 'not_answered', 'attended', '1_pressed', 'success', 'successful', 'failed', 'skipped') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE campaign_contacts MODIFY COLUMN status ENUM('pending', 'called', 'successful', 'failed', 'skipped') NOT NULL DEFAULT 'pending'");
    }
};