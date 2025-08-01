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
        Schema::create('achievement_celebrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_achievement_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('set null'); // Associated social post
            $table->enum('celebration_type', ['automatic', 'manual', 'milestone']);
            $table->text('message')->nullable(); // Custom celebration message
            $table->json('celebration_data')->nullable(); // Additional celebration data
            $table->boolean('is_public')->default(true);
            $table->integer('congratulations_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['user_achievement_id', 'celebration_type']);
            $table->index(['is_public', 'created_at']);
            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievement_celebrations');
    }
};