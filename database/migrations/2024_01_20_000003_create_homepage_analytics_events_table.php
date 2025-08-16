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
        Schema::create('homepage_analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // 'page_view', 'cta_click', 'conversion', etc.
            $table->string('session_id');
            $table->string('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('event_data')->nullable(); // Additional event-specific data
            $table->string('page_url')->nullable();
            $table->string('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->timestamp('event_timestamp');
            $table->timestamps();

            $table->index(['event_type', 'event_timestamp']);
            $table->index(['session_id', 'event_timestamp']);
            $table->index(['user_id', 'event_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_analytics_events');
    }
};
