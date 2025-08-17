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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->json('media_urls')->nullable();
            $table->string('post_type')->default('text'); // text, image, video, share, event, job
            $table->string('visibility')->default('public'); // public, connections, circles, private
            $table->json('circle_ids')->nullable();
            $table->json('group_ids')->nullable();
            $table->foreignId('shared_post_id')->nullable()->constrained('posts')->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['post_type', 'visibility']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
