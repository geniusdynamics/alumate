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
        Schema::create('crm_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Integration name (Salesforce, HubSpot, etc.)
            $table->enum('provider', ['salesforce', 'hubspot', 'pipedrive', 'zoho', 'custom']);
            $table->json('config'); // API keys, endpoints, field mappings
            $table->boolean('is_active')->default(false);
            $table->enum('sync_direction', ['push', 'pull', 'bidirectional'])->default('push');
            $table->integer('sync_interval')->default(3600); // Sync interval in seconds
            $table->timestamp('last_sync_at')->nullable();
            $table->json('last_sync_result')->nullable(); // Success/error details
            $table->json('field_mappings')->nullable(); // Map local fields to CRM fields
            $table->timestamps();

            $table->index(['provider', 'is_active']);
            $table->index(['last_sync_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_integrations');
    }
};
