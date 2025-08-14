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
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('participant'); // participant, admin, moderator
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->json('settings')->nullable(); // User-specific conversation settings
            $table->timestamps();
            
            // Unique constraint to prevent duplicate participants
            $table->unique(['conversation_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['user_id', 'last_read_at']);
            $table->index(['conversation_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
