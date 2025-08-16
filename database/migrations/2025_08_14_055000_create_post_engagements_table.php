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
        Schema::create('post_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // like, comment, share, reaction
            $table->json('data')->nullable(); // Additional data for the engagement
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index(['post_id', 'type']);
            $table->index(['user_id', 'type']);
            $table->unique(['post_id', 'user_id', 'type'], 'unique_post_user_engagement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_engagements');
    }
};
