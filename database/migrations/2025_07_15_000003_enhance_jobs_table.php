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
        Schema::table('jobs', function (Blueprint $table) {
            // Add job requirements and details
            $table->json('required_skills')->nullable()->after('description');
            $table->json('preferred_qualifications')->nullable()->after('required_skills');
            $table->enum('experience_level', ['entry', 'junior', 'mid', 'senior', 'executive'])->default('entry')->after('preferred_qualifications');
            $table->integer('min_experience_years')->default(0)->after('experience_level');
            
            // Add salary range instead of single salary
            $table->decimal('salary_min', 10, 2)->nullable()->after('min_experience_years');
            $table->decimal('salary_max', 10, 2)->nullable()->after('salary_min');
            $table->enum('salary_type', ['hourly', 'monthly', 'annually'])->default('monthly')->after('salary_max');
            
            // Add job type and work arrangement
            $table->enum('job_type', ['full_time', 'part_time', 'contract', 'internship', 'temporary'])->default('full_time')->after('salary_type');
            $table->enum('work_arrangement', ['on_site', 'remote', 'hybrid'])->default('on_site')->after('job_type');
            
            // Add application tracking
            $table->integer('total_applications')->default(0)->after('work_arrangement');
            $table->integer('viewed_applications')->default(0)->after('total_applications');
            $table->integer('shortlisted_applications')->default(0)->after('viewed_applications');
            
            // Add job status and workflow
            $table->enum('status', ['draft', 'pending_approval', 'active', 'paused', 'filled', 'expired', 'cancelled'])
                  ->default('draft')->after('shortlisted_applications');
            $table->boolean('requires_approval')->default(false)->after('status');
            $table->timestamp('approved_at')->nullable()->after('requires_approval');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            
            // Add application deadline and job duration
            $table->date('application_deadline')->nullable()->after('approved_by');
            $table->date('job_start_date')->nullable()->after('application_deadline');
            $table->date('job_end_date')->nullable()->after('job_start_date');
            
            // Add employer verification requirements
            $table->boolean('employer_verified_required')->default(true)->after('job_end_date');
            
            // Add job matching and recommendation fields
            $table->json('matching_criteria')->nullable()->after('employer_verified_required');
            $table->integer('view_count')->default(0)->after('matching_criteria');
            $table->decimal('match_score', 5, 2)->nullable()->after('view_count');
            
            // Add contact information
            $table->string('contact_email')->nullable()->after('match_score');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('contact_person')->nullable()->after('contact_phone');
            
            // Add benefits and perks
            $table->json('benefits')->nullable()->after('contact_person');
            $table->text('company_culture')->nullable()->after('benefits');
            
            // Add foreign key for approver
            $table->foreign('approved_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
        });
        
        // Drop the old salary column if it exists
        if (Schema::hasColumn('jobs', 'salary')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropColumn('salary');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'required_skills',
                'preferred_qualifications',
                'experience_level',
                'min_experience_years',
                'salary_min',
                'salary_max',
                'salary_type',
                'job_type',
                'work_arrangement',
                'total_applications',
                'viewed_applications',
                'shortlisted_applications',
                'status',
                'requires_approval',
                'approved_at',
                'approved_by',
                'application_deadline',
                'job_start_date',
                'job_end_date',
                'employer_verified_required',
                'matching_criteria',
                'view_count',
                'match_score',
                'contact_email',
                'contact_phone',
                'contact_person',
                'benefits',
                'company_culture'
            ]);
            
            // Add back the old salary column
            $table->string('salary')->nullable();
        });
    }
};