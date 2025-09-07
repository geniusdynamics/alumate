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
        Schema::create('forum_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug');
            $table->text('content');
            $table->enum('status', ['active', 'locked', 'pinned', 'archived'])->default('active');
            $table->boolean('is_sticky')->default(false);
            $table->boolean('is_announcement')->default(false);

            // Moderation
            $table->boolean('is_approved')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Statistics (denormalized for performance)
            $table->integer('posts_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);

            // Last activity tracking
            $table->foreignId('last_post_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_post_at')->nullable();

            $table->timestamps();

            $table->unique(['forum_id', 'slug']);
            $table->index(['forum_id', 'is_sticky', 'last_post_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'is_approved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_topics');
    }
};
