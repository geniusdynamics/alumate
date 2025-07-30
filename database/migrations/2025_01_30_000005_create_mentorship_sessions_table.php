<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentorship_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentorship_id')->constrained('mentorship_requests')->onDelete('cascade');
            $table->timestamp('scheduled_at');
            $table->integer('duration')->default(60); // Duration in minutes
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->json('feedback')->nullable(); // Feedback from both mentor and mentee
            $table->timestamps();

            $table->index(['mentorship_id', 'scheduled_at']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorship_sessions');
    }
};