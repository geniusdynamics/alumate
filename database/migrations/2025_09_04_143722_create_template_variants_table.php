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
        Schema::create('template_variants', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('variant_name');
            $table->json('custom_structure')->nullable(); // Custom structure overrides
            $table->json('custom_config')->nullable(); // Custom configuration overrides
            $table->json('performance_metrics')->nullable(); // Performance metrics for this variant
            $table->boolean('is_control')->default(false); // Is this the control variant
            $table->boolean('is_active')->default(true); // Is this variant active
            $table->integer('impressions')->default(0); // Number of times this variant was shown
            $table->integer('conversions')->default(0); // Number of conversions for this variant
            $table->decimal('conversion_rate', 5, 2)->default(0); // Conversion rate percentage
            $table->decimal('statistical_significance', 5, 2)->nullable(); // Statistical significance level
            $table->json('metadata')->nullable(); // Additional metadata
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['tenant_id', 'template_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_control']);
            $table->index(['template_id', 'impressions']);
            $table->index(['template_id', 'conversion_rate']);
            $table->unique(['tenant_id', 'template_id', 'variant_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_variants');
    }
};
