<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_contacts', function (Blueprint $table) {
            $table->foreignId('parent_contact_id')->nullable()->after('campaign_id')->constrained('campaign_contacts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_contacts', function (Blueprint $table) {
            $table->dropForeign(['parent_contact_id']);
            $table->dropColumn('parent_contact_id');
        });
    }
};