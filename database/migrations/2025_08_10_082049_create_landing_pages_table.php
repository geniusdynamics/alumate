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
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('target_audience', ['institution', 'employer', 'partner', 'alumni', 'general'])->default('general');
            $table->enum('campaign_type', ['onboarding', 'marketing', 'event', 'product_launch', 'trial', 'demo'])->default('marketing');
            $table->string('campaign_name')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->json('content'); // Page structure and components
            $table->json('settings'); // SEO, tracking, etc.
            $table->json('form_config')->nullable(); // Form configuration
            $table->string('template_id')->nullable(); // Reference to template used
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'target_audience']);
            $table->index(['campaign_type', 'campaign_name']);
            $table->index(['created_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
