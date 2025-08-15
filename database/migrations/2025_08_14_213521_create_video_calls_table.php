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
        Schema::create('video_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['coffee_chat', 'group_meeting', 'alumni_gathering', 'mentorship']);
            $table->enum('provider', ['jitsi', 'jitsi_videobridge', 'livekit'])->default('jitsi');
            $table->enum('status', ['scheduled', 'active', 'ended', 'cancelled'])->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('max_participants')->default(10);
            $table->string('room_id')->unique();
            $table->string('jitsi_room_name')->nullable();
            $table->text('livekit_room_token')->nullable();
            $table->json('settings')->nullable(); // recording, screen_share, etc.
            $table->timestamps();
            
            $table->index(['scheduled_at']);
            $table->index(['status']);
            $table->index(['provider']);
            $table->index(['host_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_calls');
    }
};
