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
        Schema::table('employers', function (Blueprint $table) {
            // Enhance verification status and company details
            $table->enum('verification_status', [
                'pending',
                'under_review',
                'verified',
                'rejected',
                'suspended',
                'requires_resubmission'
            ])->default('pending')->after('approved');
            
            // Add verification details
            $table->json('verification_documents')->nullable()->after('verification_status');
            $table->timestamp('verification_submitted_at')->nullable()->after('verification_documents');
            $table->timestamp('verification_completed_at')->nullable()->after('verification_submitted_at');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verification_completed_at');
            $table->text('verification_notes')->nullable()->after('verified_by');
            $table->text('rejection_reason')->nullable()->after('verification_notes');
            
            // Add comprehensive company details
            $table->string('company_registration_number')->nullable()->after('company_phone');
            $table->string('company_tax_number')->nullable()->after('company_registration_number');
            $table->string('company_website')->nullable()->after('company_tax_number');
            $table->enum('company_size', ['startup', 'small', 'medium', 'large', 'enterprise'])->nullable()->after('company_website');
            $table->string('industry')->nullable()->after('company_size');
            $table->text('company_description')->nullable()->after('industry');
            
            // Add contact person details
            $table->string('contact_person_name')->nullable()->after('company_description');
            $table->string('contact_person_title')->nullable()->after('contact_person_name');
            $table->string('contact_person_email')->nullable()->after('contact_person_title');
            $table->string('contact_person_phone')->nullable()->after('contact_person_email');
            
            // Add business information
            $table->year('established_year')->nullable()->after('contact_person_phone');
            $table->integer('employee_count')->nullable()->after('established_year');
            $table->json('business_locations')->nullable()->after('employee_count');
            $table->json('services_products')->nullable()->after('business_locations');
            
            // Add hiring statistics
            $table->integer('total_jobs_posted')->default(0)->after('services_products');
            $table->integer('active_jobs_count')->default(0)->after('total_jobs_posted');
            $table->integer('total_hires')->default(0)->after('active_jobs_count');
            $table->decimal('average_time_to_hire', 5, 2)->nullable()->after('total_hires');
            
            // Add employer rating and feedback
            $table->decimal('employer_rating', 3, 2)->nullable()->after('average_time_to_hire');
            $table->integer('total_reviews')->default(0)->after('employer_rating');
            $table->json('employer_benefits')->nullable()->after('total_reviews');
            
            // Add subscription and plan information
            $table->enum('subscription_plan', ['free', 'basic', 'premium', 'enterprise'])->default('free')->after('employer_benefits');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');
            $table->integer('job_posting_limit')->default(5)->after('subscription_expires_at');
            $table->integer('jobs_posted_this_month')->default(0)->after('job_posting_limit');
            
            // Add account status and settings
            $table->boolean('is_active')->default(true)->after('jobs_posted_this_month');
            $table->boolean('can_post_jobs')->default(false)->after('is_active');
            $table->boolean('can_search_graduates')->default(false)->after('can_post_jobs');
            $table->json('notification_preferences')->nullable()->after('can_search_graduates');
            
            // Add compliance and legal
            $table->boolean('terms_accepted')->default(false)->after('notification_preferences');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
            $table->boolean('privacy_policy_accepted')->default(false)->after('terms_accepted_at');
            $table->timestamp('privacy_policy_accepted_at')->nullable()->after('privacy_policy_accepted');
            
            // Add tracking timestamps
            $table->timestamp('last_login_at')->nullable()->after('privacy_policy_accepted_at');
            $table->timestamp('last_job_posted_at')->nullable()->after('last_login_at');
            $table->timestamp('profile_completed_at')->nullable()->after('last_job_posted_at');
            
            // Add foreign key for verifier
            $table->foreign('verified_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'verification_status',
                'verification_documents',
                'verification_submitted_at',
                'verification_completed_at',
                'verified_by',
                'verification_notes',
                'rejection_reason',
                'company_registration_number',
                'company_tax_number',
                'company_website',
                'company_size',
                'industry',
                'company_description',
                'contact_person_name',
                'contact_person_title',
                'contact_person_email',
                'contact_person_phone',
                'established_year',
                'employee_count',
                'business_locations',
                'services_products',
                'total_jobs_posted',
                'active_jobs_count',
                'total_hires',
                'average_time_to_hire',
                'employer_rating',
                'total_reviews',
                'employer_benefits',
                'subscription_plan',
                'subscription_expires_at',
                'job_posting_limit',
                'jobs_posted_this_month',
                'is_active',
                'can_post_jobs',
                'can_search_graduates',
                'notification_preferences',
                'terms_accepted',
                'terms_accepted_at',
                'privacy_policy_accepted',
                'privacy_policy_accepted_at',
                'last_login_at',
                'last_job_posted_at',
                'profile_completed_at'
            ]);
        });
    }
};