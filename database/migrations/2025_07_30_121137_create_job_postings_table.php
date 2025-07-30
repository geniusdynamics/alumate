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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->json('requirements')->nullable();
            $table->string('location')->nullable();
            $table->string('salary_range')->nullable();
            $table->foreignId('posted_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('remote_allowed')->default(false);
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship', 'temporary'])->default('full_time');
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'executive'])->default('mid');
            $table->json('skills_required')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['is_active', 'expires_at']);
            $table->index(['company_id', 'is_active']);
            $table->index(['posted_by', 'created_at']);
            $table->index('location');
            $table->index('employment_type');
            $table->index('experience_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
