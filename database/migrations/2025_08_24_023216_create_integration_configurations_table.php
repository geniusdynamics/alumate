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
        Schema::create('integration_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('institution_id');
            $table->foreign('institution_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // email_marketing, calendar, sso, crm, payment, analytics, webhook
            $table->string('provider'); // mailchimp, google, outlook, salesforce, etc.
            $table->json('configuration')->nullable(); // Provider-specific configuration
            $table->json('credentials')->nullable(); // Encrypted credentials
            $table->json('field_mappings')->nullable(); // Field mapping between systems
            $table->json('webhook_settings')->nullable(); // Webhook configuration
            $table->json('sync_settings')->nullable(); // Sync frequency and options
            $table->boolean('is_active')->default(true);
            $table->boolean('is_test_mode')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status')->nullable(); // success, failed, pending
            $table->text('sync_error')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'type']);
            $table->index(['type', 'provider']);
            $table->index(['is_active', 'sync_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_configurations');
    }
};
