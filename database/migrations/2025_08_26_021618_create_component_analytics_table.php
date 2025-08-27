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
        Schema::create('component_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_instance_id')->constrained('component_instances')->onDelete('cascade');
            $table->enum('event_type', ['view', 'click', 'conversion', 'form_submit']);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes for analytics queries performance
            $table->index(['component_instance_id', 'event_type', 'created_at'], 'analytics_query_index');
            $table->index('component_instance_id');
            $table->index('event_type');
            $table->index('created_at');
            $table->index('user_id');
            $table->index('session_id');

            // Partitioning strategy comments for large-scale analytics data:
            // Consider implementing table partitioning by created_at (monthly/quarterly)
            // for improved query performance and data management at scale.
            // Example: PARTITION BY RANGE (YEAR(created_at), MONTH(created_at))
            // This will help with data archival and query optimization for time-based analytics.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_analytics');
    }
};
