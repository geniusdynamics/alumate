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
        Schema::create('collaboration_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('landing_pages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->json('cursor_position')->nullable();
            $table->json('selected_component')->nullable();
            $table->enum('status', ['active', 'idle', 'disconnected'])->default('active');
            $table->timestamp('last_activity');
            $table->timestamps();
            
            $table->index(['page_id', 'status']);
            $table->index(['session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaboration_sessions');
    }
};
