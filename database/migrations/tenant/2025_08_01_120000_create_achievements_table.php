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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('badge_image')->nullable();
            $table->enum('category', ['career', 'education', 'community', 'milestone', 'special']);
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common');
            $table->json('criteria'); // Conditions for earning this achievement
            $table->integer('points')->default(0); // Points awarded for this achievement
            $table->boolean('is_active')->default(true);
            $table->boolean('is_auto_awarded')->default(true); // Can be automatically awarded
            $table->timestamps();

            // Indexes
            $table->index(['category', 'is_active']);
            $table->index(['rarity', 'is_active']);
            $table->index('is_auto_awarded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};