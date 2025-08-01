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
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
            $table->timestamp('earned_at');
            $table->json('metadata')->nullable(); // Additional data about how it was earned
            $table->boolean('is_featured')->default(false); // Show on profile prominently
            $table->boolean('is_notified')->default(false); // Has user been notified
            $table->timestamps();

            // Unique constraint to prevent duplicate achievements
            $table->unique(['user_id', 'achievement_id']);
            
            // Indexes
            $table->index(['user_id', 'earned_at']);
            $table->index(['user_id', 'is_featured']);
            $table->index('earned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};