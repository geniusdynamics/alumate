<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaign_donations', function (Blueprint $table) {
            $table->foreign('peer_fundraiser_id')->references('id')->on('peer_fundraisers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_donations', function (Blueprint $table) {
            $table->dropForeign(['peer_fundraiser_id']);
        });
    }
};
