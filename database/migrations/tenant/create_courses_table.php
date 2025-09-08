<?php
// ABOUTME: Migration file for creating courses table in tenant schema
// ABOUTME: Institution-specific courses without tenant_id column

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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('credits')->default(0);
            $table->integer('duration_weeks')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('max_students')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('prerequisites')->nullable(); // Array of course IDs or requirements
            $table->json('learning_outcomes')->nullable();
            $table->string('instructor_name')->nullable();
            $table->string('instructor_email')->nullable();
            $table->text('syllabus')->nullable();
            $table->json('schedule')->nullable(); // Class times, days, etc.
            $table->string('location')->nullable();
            $table->enum('delivery_mode', ['online', 'in_person', 'hybrid'])->default('online');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'start_date']);
            $table->index(['level', 'status']);
            $table->index('course_code');
            $table->index('instructor_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function