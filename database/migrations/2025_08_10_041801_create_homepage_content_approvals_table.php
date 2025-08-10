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
        Schema::create('homepage_content_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_content_id')->constrained('homepage_content')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('request_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['homepage_content_id', 'status']);
            $table->index(['reviewer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_content_approvals');
    }
};
