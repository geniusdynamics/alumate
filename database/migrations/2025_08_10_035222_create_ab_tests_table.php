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
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->enum('audience', ['individual', 'institutional', 'both'])->index();
            $table->json('variants'); // Array of variants with weights and overrides
            $table->integer('traffic_allocation')->default(100); // Percentage 0-100
            $table->json('conversion_goals'); // Array of conversion goals
            $table->timestamp('start_date')->nullable()->index();
            $table->timestamp('end_date')->nullable()->index();
            $table->enum('status', ['draft', 'running', 'paused', 'completed'])->default('draft')->index();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional test configuration
            $table->timestamps();

            // Indexes for common queries
            $table->index(['audience', 'status', 'start_date']);
            $table->index(['status', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ab_tests');
    }
};
