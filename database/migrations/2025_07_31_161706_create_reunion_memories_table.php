<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunion_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['story', 'achievement', 'memory', 'tribute', 'update'])->default('memory');
            $table->json('media_urls')->nullable(); // Associated photos/videos
            $table->json('tagged_users')->nullable(); // Users mentioned in the memory
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->enum('visibility', ['public', 'alumni_only', 'class_only'])->default('class_only');
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->datetime('memory_date')->nullable(); // When the memory took place
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['event_id', 'is_approved', 'visibility']);
            $table->index(['submitted_by', 'created_at']);
            $table->index(['is_featured', 'likes_count']);
            $table->index(['type', 'memory_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunion_memories');
    }
};