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
        Schema::create('scholarship_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('scholarship_applications')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('score', 5, 2);
            $table->text('comments');
            $table->json('criteria_scores')->nullable();
            $table->enum('recommendation', ['approve', 'reject', 'needs_more_info']);
            $table->text('feedback_for_applicant')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['application_id', 'reviewer_id']);
            $table->index(['application_id', 'recommendation']);
            $table->index(['reviewer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_reviews');
    }
};
