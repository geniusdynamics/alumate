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
        // Only create if job_postings table exists and job_applications doesn't exist
        if (Schema::hasTable('job_postings') && !Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('job_postings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', [
                'pending', 
                'reviewing', 
                'interviewing', 
                'offered', 
                'accepted', 
                'rejected', 
                'withdrawn'
            ])->default('pending');
            $table->timestamp('applied_at');
            $table->text('cover_letter')->nullable();
            $table->string('resume_url')->nullable();
            $table->boolean('introduction_requested')->default(false);
            $table->foreignId('introduction_contact_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->unique(['job_id', 'user_id']); // Prevent duplicate applications
            $table->index(['user_id', 'status']);
            $table->index(['job_id', 'status']);
            $table->index('applied_at');
            $table->index('introduction_contact_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
