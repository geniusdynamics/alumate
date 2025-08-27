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
        Schema::create('forum_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#6B7280'); // Hex color for tag
            $table->text('description')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['usage_count', 'name']);
            $table->index('is_featured');
        });

        // Pivot table for topic tags
        Schema::create('forum_topic_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('forum_topics')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('forum_tags')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['topic_id', 'tag_id']);
            $table->index('tag_id');
        });

        // Table for post likes/reactions
        Schema::create('forum_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('forum_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['like', 'helpful', 'thanks', 'agree', 'disagree'])->default('like');
            $table->timestamps();

            $table->unique(['post_id', 'user_id']);
            $table->index(['post_id', 'type']);
        });

        // Table for topic subscriptions
        Schema::create('forum_topic_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('forum_topics')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('email_notifications')->default(true);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['topic_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_tags');
    }
};
