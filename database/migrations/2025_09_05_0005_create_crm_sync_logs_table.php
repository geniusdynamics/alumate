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
        Schema::create('crm_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('crm_integration_id');
            $table->unsignedBigInteger('lead_id');
            $table->enum('sync_type', ['create', 'update', 'delete', 'pull']);
            $table->string('crm_provider');
            $table->string('crm_record_id')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->json('sync_data')->nullable(); // Data being synced
            $table->json('response_data')->nullable(); // CRM API response
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->unsignedInteger('sync_duration')->nullable(); // Duration in seconds
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'crm_integration_id']);
            $table->index(['lead_id']);
            $table->index(['status']);
            $table->index(['sync_type']);
            $table->index(['crm_provider']);
            $table->index(['created_at']);
            $table->index(['synced_at']);

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('crm_integration_id')->references('id')->on('crm_integrations')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_sync_logs');
    }
};