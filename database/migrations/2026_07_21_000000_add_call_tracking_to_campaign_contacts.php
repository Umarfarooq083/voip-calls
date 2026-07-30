<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_contacts', function (Blueprint $table) {
            $table->string('call_uuid')->nullable()->after('status');
            $table->string('channel')->nullable()->after('call_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_contacts', function (Blueprint $table) {
            $table->dropColumn(['call_uuid', 'channel']);
        });
    }
};