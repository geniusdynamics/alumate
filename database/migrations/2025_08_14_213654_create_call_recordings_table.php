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
        Schema::create('call_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('video_calls')->onDelete('cascade');
            $table->string('file_path', 500)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('format', 50)->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->text('transcription')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamps();

            $table->index(['call_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_recordings');
    }
};
