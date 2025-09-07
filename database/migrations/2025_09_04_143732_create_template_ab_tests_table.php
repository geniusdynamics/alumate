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
            $table->string('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'running', 'paused', 'completed', 'cancelled'])->default('draft');
            $table->json('goals'); // Conversion goals and target metrics
            $table->integer('traffic_allocation')->default(100); // Percentage of traffic to allocate (0-100)
            $table->enum('distribution_method', ['even', 'manual', 'weighted'])->default('even');
            $table->json('variant_distribution')->nullable(); // Distribution weights for variants
            $table->integer('minimum_sample_size')->nullable(); // Minimum sample size before evaluation
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('duration_days')->nullable(); // Expected duration in days
            $table->integer('actual_duration_days')->nullable(); // Actual duration in days
            $table->boolean('auto_end_on_significance')->default(true); // Auto-end test when significance reached
            $table->decimal('confidence_threshold', 5, 2)->default(95.0); // Statistical confidence threshold
            $table->decimal('statistical_power', 5, 2)->nullable(); // Statistical power of the test
            $table->json('winner_criteria')->nullable(); // Criteria for determining the winner
            $table->integer('winner_variant_id')->nullable(); // ID of winning variant
            $table->json('results_summary')->nullable(); // Summary of test results
            $table->json('metadata')->nullable(); // Additional test configuration
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('winner_variant_id')->references('id')->on('template_variants')->onDelete('set null');

            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_date', 'end_date']);
            $table->index(['tenant_id', 'name']);
            $table->index(['status', 'start_date']);
            $table->index(['status', 'end_date']);
            $table->index(['start_date', 'end_date']);
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
