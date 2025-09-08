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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 50)->nullable(); // Category of activity
            $table->text('description');
            $table->nullableMorphs('subject'); // The model that was acted upon
            $table->nullableMorphs('causer'); // The user who performed the action
            $table->json('properties')->nullable(); // Additional data about the activity
            $table->json('old_values')->nullable(); // Previous state for updates
            $table->json('new_values')->nullable(); // New state for updates
            $table->string('event', 50)->nullable(); // created, updated, deleted, etc.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('request_id', 100)->nullable(); // For tracing requests
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('is_system_generated')->default(false);
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['log_name']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index(['event']);
            $table->index(['occurred_at']);
            $table->index(['severity']);
            $table->index(['is_system_generated']);
            $table->index(['session_id']);
            $table->index(['request_id']);
            
            // Composite indexes for common queries
            $table->index(['log_name', 'occurred_at']);
            $table->index(['causer_type', 'causer_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};