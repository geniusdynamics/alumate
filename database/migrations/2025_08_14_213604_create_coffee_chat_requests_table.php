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
        Schema::create('coffee_chat_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('call_id')->nullable()->constrained('video_calls')->onDelete('set null');
            $table->enum('type', ['direct_request', 'ai_matched', 'open_invitation'])->default('direct_request');
            $table->json('proposed_times')->nullable(); // Array of proposed datetime slots
            $table->timestamp('selected_time')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'completed', 'expired'])->default('pending');
            $table->text('message')->nullable();
            $table->json('matching_criteria')->nullable(); // Industry, location, interests for AI matching
            $table->timestamps();
            
            $table->index(['status']);
            $table->index(['requester_id']);
            $table->index(['recipient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_chat_requests');
    }
};
