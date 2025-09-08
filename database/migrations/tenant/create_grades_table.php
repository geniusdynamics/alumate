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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->string('assessment_type', 50); // exam, quiz, assignment, project, etc.
            $table->string('assessment_name', 100);
            $table->decimal('points_earned', 8, 2);
            $table->decimal('points_possible', 8, 2);
            $table->decimal('percentage', 5, 2)->storedAs('(points_earned / points_possible) * 100');
            $table->char('letter_grade', 2)->nullable();
            $table->decimal('weight', 5, 2)->default(1.00); // Weight in final grade calculation
            $table->date('assessment_date');
            $table->date('due_date')->nullable();
            $table->boolean('is_extra_credit')->default(false);
            $table->boolean('is_dropped')->default(false); // For dropping lowest grades
            $table->text('comments')->nullable();
            $table->string('graded_by', 100)->nullable(); // Instructor/TA name
            $table->timestamp('graded_at')->nullable();
            $table->json('rubric_scores')->nullable(); // Detailed rubric breakdown
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['student_id', 'course_id']);
            $table->index(['enrollment_id']);
            $table->index(['assessment_type']);
            $table->index(['assessment_date']);
            $table->index(['percentage']);
            $table->index(['is_dropped']);
            
            // Composite indexes for common queries
            $table->index(['student_id', 'assessment_type']);
            $table->index(['course_id', 'assessment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};