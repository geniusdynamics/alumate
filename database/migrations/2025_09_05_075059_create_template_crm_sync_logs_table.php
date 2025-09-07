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
        Schema::create('template_crm_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('template_crm_integration_id');
            $table->unsignedBigInteger('template_id');
            $table->string('sync_type'); // create, update, delete
            $table->string('crm_provider');
            $table->string('crm_record_id')->nullable(); // ID in the CRM system
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->json('sync_data')->nullable(); // data that was synced
            $table->json('response_data')->nullable(); // response from CRM
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('template_crm_integration_id')->references('id')->on('template_crm_integrations');
            $table->foreign('template_id')->references('id')->on('templates');
            $table->index(['tenant_id', 'status']);
            $table->index(['template_crm_integration_id', 'created_at']);
            $table->index(['sync_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_crm_sync_logs');
    }
};