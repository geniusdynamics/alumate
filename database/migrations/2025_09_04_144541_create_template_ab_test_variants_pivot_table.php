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
        Schema::create('template_ab_test_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ab_test_id');
            $table->unsignedBigInteger('variant_id');
            $table->integer('traffic_weight')->default(50); // Percentage of traffic for this variant in the test
            $table->boolean('is_control')->default(false); // Whether this is the control variant for the test
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('ab_test_id')->references('id')->on('template_ab_tests')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('template_variants')->onDelete('cascade');

            // Ensure unique combinations and proper indexes
            $table->unique(['ab_test_id', 'variant_id']);
            $table->index(['ab_test_id', 'traffic_weight']);
            $table->index(['ab_test_id', 'is_control']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_ab_test_variants');
    }
};
