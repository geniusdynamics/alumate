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
        Schema::create('landing_page_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('category', ['institution', 'employer', 'partner', 'alumni', 'general'])->default('general');
            $table->json('content'); // Template structure
            $table->json('default_settings'); // Default SEO and settings
            $table->string('preview_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->integer('usage_count')->default(0);
            $table->json('tags')->nullable(); // For categorization
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['is_active', 'is_premium']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_templates');
    }
};
