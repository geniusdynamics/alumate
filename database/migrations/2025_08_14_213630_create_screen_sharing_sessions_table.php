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
        Schema::create('screen_sharing_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('video_calls')->onDelete('cascade');
            $table->foreignId('presenter_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->json('session_data')->nullable(); // Screen dimensions, quality settings
            $table->timestamps();
            
            $table->index(['call_id']);
            $table->index(['presenter_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_sharing_sessions');
    }
};
