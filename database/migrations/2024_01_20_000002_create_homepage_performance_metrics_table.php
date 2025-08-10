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
        Schema::create('homepage_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // 'page_load', 'api_response', 'database_query', etc.
            $table->string('metric_name');
            $table->decimal('value', 10, 3); // Metric value (e.g., response time in ms)
            $table->string('unit'); // 'ms', 'seconds', 'bytes', etc.
            $table->string('environment');
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['metric_type', 'metric_name', 'recorded_at']);
            $table->index(['environment', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_performance_metrics');
    }
};