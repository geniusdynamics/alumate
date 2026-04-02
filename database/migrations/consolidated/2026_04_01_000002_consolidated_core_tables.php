<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Core Tables Migration
 *
 * Combines foundational table migrations:
 * - 2019_09_15_000010_create_tenants_table.php (Stancl tenancy)
 * - 2019_09_15_000020_create_domains_table.php (Stancl domains)
 * - 2024_01_01_000002_create_permission_tables.php (Spatie permissions)
 * - 2024_01_01_000005_create_employers_table.php
 * - 2024_01_01_000006_create_jobs_table.php
 * - 2024_01_01_000007_create_courses_table.php
 * - 2024_01_01_000010_create_recommendations_table.php
 * - 2024_12_01_000000_create_institutions_table.php
 * - 2024_12_02_000000_create_companies_table.php
 * - 2024_01_15_000001_create_global_tenancy_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tenants table (Stancl tenancy)
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->timestamps();
                $table->json('data')->nullable();
            });
        }

        // Add deleted_at to tenants if not exists
        if (Schema::hasTable('tenants') && ! Schema::hasColumn('tenants', 'deleted_at')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Domains table (Stancl tenancy)
        if (! Schema::hasTable('domains')) {
            Schema::create('domains', function (Blueprint $table) {
                $table->increments('id');
                $table->string('domain', 255)->unique();
                $table->string('tenant_id');
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('cascade');
            });
        }

        // Institutions table
        if (! Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->default('university');
                $table->string('country')->nullable();
                $table->string('website')->nullable();
                $table->string('logo')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['type', 'is_active']);
                $table->index(['country']);
            });
        }

        // Companies table
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('industry')->nullable();
                $table->string('size')->nullable();
                $table->string('website')->nullable();
                $table->string('logo')->nullable();
                $table->text('description')->nullable();
                $table->string('country')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['industry', 'is_active']);
                $table->index(['country']);
            });
        }

        // Employers table
        if (! Schema::hasTable('employers')) {
            Schema::create('employers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
                $table->string('company_name')->nullable();
                $table->string('job_title')->nullable();
                $table->text('bio')->nullable();
                $table->string('website')->nullable();
                $table->string('logo')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_approved')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_verified', 'is_active']);
            });
        }

        // Courses table
        if (! Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('department')->nullable();
                $table->string('level')->nullable();
                $table->text('description')->nullable();
                $table->integer('duration_years')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['department', 'is_active']);
            });
        }

        // Jobs table (base)
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
                $table->string('title');
                $table->text('description');
                $table->string('location')->nullable();
                $table->json('required_skills')->nullable();
                $table->json('preferred_qualifications')->nullable();
                $table->string('experience_level')->nullable();
                $table->integer('min_experience_years')->nullable();
                $table->decimal('salary_min', 12, 2)->nullable();
                $table->decimal('salary_max', 12, 2)->nullable();
                $table->string('salary_type')->nullable();
                $table->string('job_type')->nullable();
                $table->string('work_arrangement')->nullable();
                $table->integer('total_applications')->default(0);
                $table->integer('viewed_applications')->default(0);
                $table->integer('shortlisted_applications')->default(0);
                $table->string('status')->default('draft');
                $table->boolean('requires_approval')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('application_deadline')->nullable();
                $table->timestamp('job_start_date')->nullable();
                $table->timestamp('job_end_date')->nullable();
                $table->boolean('employer_verified_required')->default(false);
                $table->json('matching_criteria')->nullable();
                $table->integer('view_count')->default(0);
                $table->decimal('match_score', 5, 2)->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('contact_person')->nullable();
                $table->json('benefits')->nullable();
                $table->text('company_culture')->nullable();
                $table->text('approval_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['employer_id', 'status']);
                $table->index(['status', 'created_at']);
                $table->index(['course_id', 'status']);
            });
        }

        // Job applications table
        if (! Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('status')->default('pending');
                $table->text('cover_letter')->nullable();
                $table->string('resume_path')->nullable();
                $table->json('application_data')->nullable();
                $table->decimal('match_score', 5, 2)->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['job_id', 'user_id']);
                $table->index(['status', 'created_at']);
            });
        }

        // Recommendations table
        if (! Schema::hasTable('recommendations')) {
            Schema::create('recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('recommender_name');
                $table->string('recommender_email');
                $table->string('recommender_relationship');
                $table->text('content');
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'status']);
            });
        }

        // Cache table
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        // Cache locks table
        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('employers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
