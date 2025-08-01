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
        Schema::create('student_alumni_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('alumni_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('success_story_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('connection_type', ['mentorship', 'networking', 'advice', 'collaboration']);
            $table->enum('status', ['pending', 'accepted', 'declined', 'blocked'])->default('pending');
            $table->text('student_message')->nullable();
            $table->text('alumni_response')->nullable();
            $table->json('connection_data')->nullable(); // Additional connection metadata
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['student_id', 'status']);
            $table->index(['alumni_id', 'status']);
            $table->index(['connection_type', 'status']);
            $table->index('requested_at');
            $table->unique(['student_id', 'alumni_id', 'connection_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_alumni_connections');
    }
};
