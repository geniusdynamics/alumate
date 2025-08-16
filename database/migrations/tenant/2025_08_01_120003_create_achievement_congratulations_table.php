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
        Schema::create('achievement_congratulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_celebration_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who congratulated
            $table->text('message')->nullable(); // Optional congratulations message
            $table->timestamps();

            // Unique constraint to prevent duplicate congratulations
            $table->unique(['achievement_celebration_id', 'user_id'], 'unique_congratulations');

            // Indexes
            $table->index(['achievement_celebration_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievement_congratulations');
    }
};
