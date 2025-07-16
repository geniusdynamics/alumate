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
        // Add indexes for better query performance
        Schema::table('graduates', function (Blueprint $table) {
            $table->index(['employment_status']);
            $table->index(['job_search_active']);
            $table->index(['graduation_year']);
            $table->index(['course_id', 'graduation_year']);
            $table->index(['tenant_id', 'employment_status']);
            $table->index(['profile_completion_percentage']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index(['is_active']);
            $table->index(['level']);
            $table->index(['institution_id', 'is_active']);
            $table->index(['employment_rate']);
            $table->index(['completion_rate']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['status']);
            $table->index(['job_type']);
            $table->index(['experience_level']);
            $table->index(['employer_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index(['application_deadline']);
            $table->index(['created_at']);
            $table->index(['requires_approval', 'status']);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->index(['status']);
            $table->index(['priority']);
            $table->index(['job_id', 'status']);
            $table->index(['graduate_id', 'status']);
            $table->index(['created_at']);
            $table->index(['interview_scheduled_at']);
            $table->index(['is_flagged']);
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->index(['verification_status']);
            $table->index(['is_active']);
            $table->index(['can_post_jobs']);
            $table->index(['subscription_plan']);
            $table->index(['industry']);
            $table->index(['company_size']);
        });

        // Add unique constraints where needed
        Schema::table('courses', function (Blueprint $table) {
            $table->unique(['code', 'institution_id']);
        });

        // Add check constraints for data integrity
        Schema::table('graduates', function (Blueprint $table) {
            DB::statement('ALTER TABLE graduates ADD CONSTRAINT chk_profile_completion CHECK (profile_completion_percentage >= 0 AND profile_completion_percentage <= 100)');
            DB::statement('ALTER TABLE graduates ADD CONSTRAINT chk_gpa CHECK (gpa >= 0 AND gpa <= 4.0)');
        });

        Schema::table('jobs', function (Blueprint $table) {
            DB::statement('ALTER TABLE jobs ADD CONSTRAINT chk_salary_range CHECK (salary_min <= salary_max OR salary_min IS NULL OR salary_max IS NULL)');
            DB::statement('ALTER TABLE jobs ADD CONSTRAINT chk_experience_years CHECK (min_experience_years >= 0)');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            DB::statement('ALTER TABLE job_applications ADD CONSTRAINT chk_employer_rating CHECK (employer_rating >= 1 AND employer_rating <= 5 OR employer_rating IS NULL)');
            DB::statement('ALTER TABLE job_applications ADD CONSTRAINT chk_match_score CHECK (match_score >= 0 AND match_score <= 100 OR match_score IS NULL)');
        });

        Schema::table('employers', function (Blueprint $table) {
            DB::statement('ALTER TABLE employers ADD CONSTRAINT chk_employer_rating CHECK (employer_rating >= 0 AND employer_rating <= 5 OR employer_rating IS NULL)');
            DB::statement('ALTER TABLE employers ADD CONSTRAINT chk_employee_count CHECK (employee_count >= 0 OR employee_count IS NULL)');
            DB::statement('ALTER TABLE employers ADD CONSTRAINT chk_job_limits CHECK (jobs_posted_this_month >= 0 AND job_posting_limit >= 0)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop check constraints
        DB::statement('ALTER TABLE graduates DROP CONSTRAINT IF EXISTS chk_profile_completion');
        DB::statement('ALTER TABLE graduates DROP CONSTRAINT IF EXISTS chk_gpa');
        DB::statement('ALTER TABLE jobs DROP CONSTRAINT IF EXISTS chk_salary_range');
        DB::statement('ALTER TABLE jobs DROP CONSTRAINT IF EXISTS chk_experience_years');
        DB::statement('ALTER TABLE job_applications DROP CONSTRAINT IF EXISTS chk_employer_rating');
        DB::statement('ALTER TABLE job_applications DROP CONSTRAINT IF EXISTS chk_match_score');
        DB::statement('ALTER TABLE employers DROP CONSTRAINT IF EXISTS chk_employer_rating');
        DB::statement('ALTER TABLE employers DROP CONSTRAINT IF EXISTS chk_employee_count');
        DB::statement('ALTER TABLE employers DROP CONSTRAINT IF EXISTS chk_job_limits');

        // Drop unique constraints
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code', 'institution_id']);
        });

        // Drop indexes
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropIndex(['employment_status']);
            $table->dropIndex(['job_search_active']);
            $table->dropIndex(['graduation_year']);
            $table->dropIndex(['course_id', 'graduation_year']);
            $table->dropIndex(['tenant_id', 'employment_status']);
            $table->dropIndex(['profile_completion_percentage']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['level']);
            $table->dropIndex(['institution_id', 'is_active']);
            $table->dropIndex(['employment_rate']);
            $table->dropIndex(['completion_rate']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['job_type']);
            $table->dropIndex(['experience_level']);
            $table->dropIndex(['employer_id', 'status']);
            $table->dropIndex(['course_id', 'status']);
            $table->dropIndex(['application_deadline']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['requires_approval', 'status']);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['job_id', 'status']);
            $table->dropIndex(['graduate_id', 'status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['interview_scheduled_at']);
            $table->dropIndex(['is_flagged']);
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['can_post_jobs']);
            $table->dropIndex(['subscription_plan']);
            $table->dropIndex(['industry']);
            $table->dropIndex(['company_size']);
        });
    }
};