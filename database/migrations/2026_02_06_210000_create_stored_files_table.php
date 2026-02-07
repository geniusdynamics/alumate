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
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('path'); // Storage path
            $table->string('filename'); // Original filename
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // Size in bytes
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->string('cdn_url')->nullable(); // CDN URL for public files
            $table->json('thumbnails')->nullable(); // JSON object with thumbnail URLs
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, duration, etc.)
            $table->enum('virus_scan_status', ['pending', 'clean', 'infected'])->default('pending');
            $table->timestamp('scanned_at')->nullable();
            $table->string('storage_disk')->default('s3'); // s3, spaces, local
            $table->string('collection')->nullable()->index(); // Grouping (avatars, attachments, etc.)
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['collection', 'created_at']);
            $table->index('virus_scan_status');
            $table->index(['visibility', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
