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
        Schema::create('graduates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('student_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->integer('graduation_year');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('gpa', 3, 2)->nullable();
            $table->string('academic_standing')->nullable();
            $table->string('employment_status')->default('unemployed');
            $table->string('current_job_title')->nullable();
            $table->string('current_company')->nullable();
            $table->decimal('current_salary', 10, 2)->nullable();
            $table->date('employment_start_date')->nullable();
            $table->decimal('profile_completion_percentage', 5, 2)->default(0);
            $table->json('profile_completion_fields')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->boolean('allow_employer_contact')->default(true);
            $table->boolean('job_search_active')->default(false);
            $table->timestamp('last_profile_update')->nullable();
            $table->timestamp('last_employment_update')->nullable();
            $table->string('profile_visibility')->default('public');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['tenant_id', 'graduation_year']);
            $table->index(['employment_status']);
            $table->index(['job_search_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('graduates');
    }
};
