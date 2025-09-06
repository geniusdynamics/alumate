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
        Schema::create('publishing_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('published_site_id')->constrained()->onDelete('cascade');
            $table->string('tenant_id');
            $table->string('workflow_name');
            $table->string('status')->default('draft'); // draft, active, paused, completed
            $table->string('trigger_type')->default('manual'); // manual, scheduled, webhook
            $table->json('approval_steps')->nullable();
            $table->json('current_step')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->json('workflow_config')->nullable();
            $table->json('execution_history')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['published_site_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'scheduled_at']);
            
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_workflows');
    }
};