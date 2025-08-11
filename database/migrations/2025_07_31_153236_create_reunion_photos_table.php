<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunion_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->json('metadata')->nullable(); // EXIF data, dimensions, etc.
            $table->json('tagged_users')->nullable(); // Array of user IDs tagged in photo
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(true); // For moderation
            $table->enum('visibility', ['public', 'alumni_only', 'class_only'])->default('class_only');
            $table->datetime('taken_at')->nullable(); // When photo was taken
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['event_id', 'is_approved', 'visibility']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['is_featured', 'likes_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunion_photos');
    }
};
