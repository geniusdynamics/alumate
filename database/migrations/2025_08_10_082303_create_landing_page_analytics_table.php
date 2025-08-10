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
        Schema::create('landing_page_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // page_view, form_submit, button_click, etc.
            $table->string('event_name')->nullable();
            $table->json('event_data')->nullable(); // Additional event data
            $table->string('session_id')->nullable();
            $table->string('visitor_id')->nullable(); // Anonymous visitor tracking
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->json('utm_data')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('event_time');
            $table->timestamps();
            
            $table->index(['landing_page_id', 'event_type']);
            $table->index(['event_time', 'event_type']);
            $table->index(['session_id', 'visitor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_analytics');
    }
};
