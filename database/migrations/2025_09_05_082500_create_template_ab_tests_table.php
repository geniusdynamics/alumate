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
        Schema::create('template_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('variants'); // Array of variant configurations
            $table->string('status')->default('draft'); // draft, active, paused, completed
            $table->string('goal_metric')->default('conversion_rate'); // conversion_rate, click_rate, time_on_page
            $table->decimal('confidence_threshold', 5, 4)->default(0.95); // Statistical significance threshold
            $table->integer('sample_size_per_variant')->default(1000);
            $table->json('traffic_distribution')->nullable(); // Manual traffic split percentages
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('results')->nullable(); // Statistical analysis results
            $table->timestamps();

            // Indexes
            $table->index(['template_id', 'status']);
            $table->index(['status', 'started_at']);
            $table->index('goal_metric');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_ab_tests');
    }
};