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
        Schema::create('ab_test_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained('template_ab_tests')->onDelete('cascade');
            $table->string('variant_id'); // Identifier for the variant (A, B, C, etc.)
            $table->string('event_type'); // page_view, conversion, click, time_on_page
            $table->string('session_id')->nullable(); // User session identifier
            $table->json('event_data')->nullable(); // Additional event data
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['ab_test_id', 'variant_id']);
            $table->index(['ab_test_id', 'event_type']);
            $table->index(['session_id', 'ab_test_id']);
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ab_test_events');
    }
};