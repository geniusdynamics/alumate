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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'withdrawn', 'failed'])
                  ->default('enrolled');
            $table->date('enrollment_date');
            $table->date('completion_date')->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->char('letter_grade', 2)->nullable();
            $table->decimal('grade_points', 3, 2)->nullable();
            $table->integer('credits_earned')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['student_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index(['enrollment_date']);
            $table->index(['completion_date']);
            $table->index(['final_grade']);
            
            // Unique constraint to prevent duplicate enrollments
            $table->unique(['student_id', 'course_id'], 'unique_student_course_enrollment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};