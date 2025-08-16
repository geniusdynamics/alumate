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
        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_id')->constrained()->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'awarded']);
            $table->json('application_data');
            $table->json('documents')->nullable();
            $table->text('personal_statement');
            $table->decimal('gpa', 3, 2)->nullable();
            $table->text('financial_need_statement')->nullable();
            $table->json('references')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['scholarship_id', 'applicant_id']);
            $table->index(['scholarship_id', 'status']);
            $table->index(['applicant_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_applications');
    }
};
