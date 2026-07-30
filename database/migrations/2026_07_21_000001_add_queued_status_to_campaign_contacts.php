<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaign_contacts MODIFY COLUMN status ENUM('pending', 'called', 'successful', 'failed', 'skipped', 'queued', 'calling') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaign_contacts MODIFY COLUMN status ENUM('pending', 'called', 'successful', 'failed', 'skipped') NOT NULL DEFAULT 'pending'");
        }
    }
};