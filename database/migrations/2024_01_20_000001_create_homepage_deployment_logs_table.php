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
        Schema::create('homepage_deployment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('deployment_id')->unique();
            $table->string('version');
            $table->string('environment');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'rolled_back']);
            $table->json('deployment_data')->nullable();
            $table->json('migration_results')->nullable();
            $table->json('verification_results')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('deployed_by')->nullable();
            $table->string('commit_hash')->nullable();
            $table->json('rollback_data')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'environment']);
            $table->index(['started_at', 'environment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_deployment_logs');
    }
};