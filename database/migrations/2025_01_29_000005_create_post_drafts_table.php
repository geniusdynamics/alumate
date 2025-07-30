<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content')->nullable();
            $table->json('media_urls')->nullable();
            $table->enum('post_type', ['text', 'media', 'career_update', 'achievement', 'event'])->default('text');
            $table->enum('visibility', ['public', 'circles', 'groups', 'specific'])->default('public');
            $table->json('circle_ids')->nullable();
            $table->json('group_ids')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->json('media_urls')->nullable();
            $table->enum('post_type', ['text', 'media', 'career_update', 'achievement', 'event'])->default('text');
            $table->enum('visibility', ['public', 'circles', 'groups', 'specific'])->default('public');
            $table->json('circle_ids')->nullable();
            $table->json('group_ids')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('scheduled_for');
            $table->enum('status', ['pending', 'published', 'failed'])->default('pending');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['scheduled_for', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
        Schema::dropIfExists('post_drafts');
    }
};