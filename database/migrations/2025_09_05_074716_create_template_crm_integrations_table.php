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
        Schema::create('template_crm_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('provider'); // hubspot, salesforce, pipedrive, etc.
            $table->json('config'); // API keys, endpoints, etc.
            $table->boolean('is_active')->default(true);
            $table->enum('sync_direction', ['one_way', 'two_way'])->default('one_way');
            $table->integer('sync_interval')->default(3600); // seconds
            $table->timestamp('last_sync_at')->nullable();
            $table->json('last_sync_result')->nullable();
            $table->json('field_mappings')->nullable(); // template field to CRM field mapping
            $table->json('sync_filters')->nullable(); // filters for what templates to sync
            $table->string('webhook_secret')->nullable(); // for webhook verification
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->index(['tenant_id', 'provider']);
            $table->index(['is_active', 'last_sync_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_crm_integrations');
    }
};
