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
        Schema::create('site_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('published_site_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('deployment_id')->unique();
            $table->string('status')->default('pending'); // pending, deploying, deployed, failed, rolled_back
            $table->string('trigger_type')->default('manual'); // manual, auto, webhook
            $table->string('build_hash')->nullable();
            $table->json('build_config')->nullable();
            $table->json('deployment_config')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->json('deployment_logs')->nullable();
            $table->string('rollback_hash')->nullable();
            $table->boolean('is_rollback')->default(false);
            $table->timestamps();

            // Indexes for performance
            $table->index(['published_site_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['deployment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_deployments');
    }
};
