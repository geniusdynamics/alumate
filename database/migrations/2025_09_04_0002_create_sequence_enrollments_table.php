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
        Schema::create('sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sequence_id');
            $table->foreignId('lead_id')->constrained('leads');
            $table->integer('current_step')->default(0);
            $table->enum('status', ['active', 'completed', 'paused', 'unsubscribed'])->default('active');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['sequence_id', 'lead_id'], 'unique_enrollment');

            // Indexes
            $table->index('status', 'idx_status');
            $table->index(['status', 'current_step'], 'idx_next_send');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_enrollments');
    }
};