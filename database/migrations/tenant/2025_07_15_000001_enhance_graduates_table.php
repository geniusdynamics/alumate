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
        Schema::table('graduates', function (Blueprint $table) {
            // Add employment status tracking
            $table->enum('employment_status', ['unemployed', 'employed', 'self_employed', 'further_studies', 'other'])
                  ->default('unemployed')->after('course_id');
            
            // Add current job information
            $table->string('current_job_title')->nullable()->after('employment_status');
            $table->string('current_company')->nullable()->after('current_job_title');
            $table->decimal('current_salary', 10, 2)->nullable()->after('current_company');
            $table->date('employment_start_date')->nullable()->after('current_salary');
            
            // Add profile completion tracking
            $table->decimal('profile_completion_percentage', 5, 2)->default(0)->after('employment_start_date');
            $table->json('profile_completion_fields')->nullable()->after('profile_completion_percentage');
            
            // Add privacy settings
            $table->json('privacy_settings')->nullable()->after('profile_completion_fields');
            
            // Add student ID for better tracking
            $table->string('student_id')->nullable()->unique()->after('tenant_id');
            
            // Add GPA and academic performance
            $table->decimal('gpa', 3, 2)->nullable()->after('graduation_year');
            $table->enum('academic_standing', ['excellent', 'very_good', 'good', 'satisfactory', 'pass'])->nullable()->after('gpa');
            
            // Add skills and certifications
            $table->json('skills')->nullable()->after('academic_standing');
            $table->json('certifications')->nullable()->after('skills');
            
            // Add contact preferences
            $table->boolean('allow_employer_contact')->default(true)->after('privacy_settings');
            $table->boolean('job_search_active')->default(true)->after('allow_employer_contact');
            
            // Add timestamps for better tracking
            $table->timestamp('last_profile_update')->nullable()->after('job_search_active');
            $table->timestamp('last_employment_update')->nullable()->after('last_profile_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropColumn([
                'employment_status',
                'current_job_title',
                'current_company',
                'current_salary',
                'employment_start_date',
                'profile_completion_percentage',
                'profile_completion_fields',
                'privacy_settings',
                'student_id',
                'gpa',
                'academic_standing',
                'skills',
                'certifications',
                'allow_employer_contact',
                'job_search_active',
                'last_profile_update',
                'last_employment_update'
            ]);
        });
    }
};