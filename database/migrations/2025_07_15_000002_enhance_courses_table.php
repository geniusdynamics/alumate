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
        Schema::table('courses', function (Blueprint $table) {
            // Add institution relationship (for central courses table)
            $table->string('institution_id')->nullable()->after('id');
            
            // Add course details
            $table->string('code')->unique()->after('name');
            $table->enum('level', ['certificate', 'diploma', 'advanced_diploma', 'degree', 'other'])->after('code');
            $table->integer('duration_months')->after('level');
            $table->enum('study_mode', ['full_time', 'part_time', 'online', 'hybrid'])->after('duration_months');
            
            // Add skill mappings
            $table->json('required_skills')->nullable()->after('description');
            $table->json('skills_gained')->nullable()->after('required_skills');
            $table->json('career_paths')->nullable()->after('skills_gained');
            
            // Add course status and visibility
            $table->boolean('is_active')->default(true)->after('career_paths');
            $table->boolean('is_featured')->default(false)->after('is_active');
            
            // Add enrollment and completion tracking
            $table->integer('total_enrolled')->default(0)->after('is_featured');
            $table->integer('total_graduated')->default(0)->after('total_enrolled');
            $table->decimal('completion_rate', 5, 2)->default(0)->after('total_graduated');
            
            // Add employment statistics
            $table->decimal('employment_rate', 5, 2)->default(0)->after('completion_rate');
            $table->decimal('average_salary', 10, 2)->nullable()->after('employment_rate');
            
            // Add course metadata
            $table->json('prerequisites')->nullable()->after('average_salary');
            $table->json('learning_outcomes')->nullable()->after('prerequisites');
            $table->string('department')->nullable()->after('learning_outcomes');
            
            // Add foreign key constraint for institution
            $table->foreign('institution_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn([
                'institution_id',
                'code',
                'level',
                'duration_months',
                'study_mode',
                'required_skills',
                'skills_gained',
                'career_paths',
                'is_active',
                'is_featured',
                'total_enrolled',
                'total_graduated',
                'completion_rate',
                'employment_rate',
                'average_salary',
                'prerequisites',
                'learning_outcomes',
                'department'
            ]);
        });
    }
};