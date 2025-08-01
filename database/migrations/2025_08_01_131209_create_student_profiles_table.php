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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('student_id')->unique();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->integer('current_year')->default(1); // 1st year, 2nd year, etc.
            $table->integer('expected_graduation_year');
            $table->enum('enrollment_status', ['active', 'inactive', 'suspended', 'graduated'])->default('active');
            $table->date('enrollment_date');
            $table->decimal('current_gpa', 3, 2)->nullable();
            $table->enum('academic_standing', ['excellent', 'good', 'satisfactory', 'probation'])->default('good');

            // Career interests and goals
            $table->json('career_interests')->nullable(); // Array of career fields/industries
            $table->json('skills')->nullable(); // Array of current skills
            $table->json('learning_goals')->nullable(); // Array of learning objectives
            $table->text('career_goals')->nullable(); // Long-term career aspirations

            // Alumni connection preferences
            $table->boolean('seeking_mentorship')->default(false);
            $table->json('mentorship_interests')->nullable(); // Areas where they want mentorship
            $table->boolean('interested_in_alumni_stories')->default(true);
            $table->boolean('interested_in_networking')->default(true);
            $table->boolean('interested_in_events')->default(true);

            // Profile completion and privacy
            $table->decimal('profile_completion_percentage', 5, 2)->default(0);
            $table->json('profile_completion_fields')->nullable();
            $table->json('privacy_settings')->nullable();

            // Contact preferences
            $table->boolean('allow_alumni_contact')->default(true);
            $table->boolean('allow_mentor_requests')->default(true);
            $table->boolean('allow_event_invitations')->default(true);

            // Timestamps
            $table->timestamp('last_profile_update')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'enrollment_status']);
            $table->index(['course_id', 'current_year']);
            $table->index(['expected_graduation_year', 'enrollment_status']);
            $table->index('seeking_mentorship');
            $table->index('enrollment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
