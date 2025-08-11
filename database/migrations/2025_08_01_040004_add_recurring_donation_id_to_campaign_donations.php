<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_donations', function (Blueprint $table) {
            $table->foreignId('recurring_donation_id')->nullable()->after('peer_fundraiser_id')->constrained('recurring_donations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_donations', function (Blueprint $table) {
            $table->dropForeign(['recurring_donation_id']);
            $table->dropColumn('recurring_donation_id');
        });
    }
};
