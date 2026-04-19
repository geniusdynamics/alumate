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
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->string('gamification_type')->nullable()->after('event_name');
            $table->integer('points_earned')->nullable()->after('gamification_type');
            $table->string('badge_earned')->nullable()->after('points_earned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropColumn(['gamification_type', 'points_earned', 'badge_earned']);
        });
    }
};