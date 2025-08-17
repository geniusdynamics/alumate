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
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->enum('type', ['email', 'call', 'meeting', 'demo', 'proposal', 'follow_up', 'note', 'status_change', 'score_change']);
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional activity data
            $table->enum('outcome', ['positive', 'neutral', 'negative'])->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['lead_id', 'type']);
            $table->index(['created_by', 'created_at']);
            $table->index(['scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
