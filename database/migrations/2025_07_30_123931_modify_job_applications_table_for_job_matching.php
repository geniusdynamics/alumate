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
        Schema::table('job_applications', function (Blueprint $table) {
            // Add new columns for job matching system if they don't exist
            if (! Schema::hasColumn('job_applications', 'introduction_requested')) {
                $table->boolean('introduction_requested')->default(false);
            }

            if (! Schema::hasColumn('job_applications', 'introduction_contact_id')) {
                $table->foreignId('introduction_contact_id')->nullable()->constrained('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('job_applications', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('job_applications', 'applied_at')) {
                $table->timestamp('applied_at')->nullable();
            }

            if (! Schema::hasColumn('job_applications', 'resume_url')) {
                $table->string('resume_url')->nullable();
            }
        });

        // Add indexes in a separate schema call to avoid conflicts
        try {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'job_applications_user_status_idx');
                $table->index(['job_id', 'status'], 'job_applications_job_status_idx');
                $table->index('applied_at', 'job_applications_applied_at_idx');
                $table->index('introduction_contact_id', 'job_applications_intro_contact_idx');
            });
        } catch (\Exception $e) {
            // Indexes might already exist, ignore the error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // Remove indexes first
            try {
                $table->dropIndex('job_applications_user_status_idx');
                $table->dropIndex('job_applications_job_status_idx');
                $table->dropIndex('job_applications_applied_at_idx');
                $table->dropIndex('job_applications_intro_contact_idx');
            } catch (\Exception $e) {
                // Indexes might not exist, ignore the error
            }

            // Remove columns added for job matching
            $table->dropColumn([
                'introduction_requested',
                'introduction_contact_id',
                'notes',
                'applied_at',
                'resume_url',
            ]);
        });
    }
};
