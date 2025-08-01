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
        Schema::create('success_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('summary');
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->json('media_urls')->nullable(); // Array of images, videos, documents
            $table->string('industry')->nullable();
            $table->string('achievement_type'); // promotion, award, startup, publication, etc.
            $table->string('current_role')->nullable();
            $table->string('current_company')->nullable();
            $table->string('graduation_year')->nullable();
            $table->string('degree_program')->nullable();
            $table->json('tags')->nullable(); // Array of tags for categorization
            $table->json('demographics')->nullable(); // For diversity showcase
            $table->enum('status', ['draft', 'published', 'featured', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_social_sharing')->default(true);
            $table->integer('view_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('featured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['status', 'published_at']);
            $table->index(['industry', 'achievement_type']);
            $table->index(['is_featured', 'featured_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('success_stories');
    }
};
