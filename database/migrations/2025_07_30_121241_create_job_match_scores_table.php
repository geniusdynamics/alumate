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
        // Only create if job_postings table exists
        if (Schema::hasTable('job_postings')) {
            Schema::create('job_match_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('job_postings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 5, 2); // Overall match score (0-100)
            $table->json('reasons')->nullable(); // Detailed reasons for the match
            $table->timestamp('calculated_at');
            $table->decimal('connection_score', 5, 2)->default(0); // Score from network connections
            $table->decimal('skills_score', 5, 2)->default(0); // Score from skills match
            $table->decimal('education_score', 5, 2)->default(0); // Score from education relevance
            $table->decimal('circle_score', 5, 2)->default(0); // Score from circle overlap
            $table->integer('mutual_connections_count')->default(0); // Number of mutual connections
            $table->timestamps();

            // Indexes for performance
            $table->unique(['job_id', 'user_id']); // One score per job-user pair
            $table->index(['user_id', 'score']); // For user's job recommendations
            $table->index(['job_id', 'score']); // For job's best candidates
            $table->index('calculated_at'); // For finding stale scores
            $table->index('score'); // For filtering by score ranges
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_match_scores');
    }
};
