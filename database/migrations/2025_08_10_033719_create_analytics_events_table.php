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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 100)->index();
            $table->enum('audience', ['individual', 'institutional'])->index();
            $table->string('section', 100)->index();
            $table->string('action', 100)->index();
            $table->decimal('value', 10, 2)->nullable();
            $table->json('custom_data')->nullable();
            $table->string('session_id', 100)->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('timestamp')->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['audience', 'event_name', 'timestamp']);
            $table->index(['session_id', 'timestamp']);
            $table->index(['audience', 'section', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
