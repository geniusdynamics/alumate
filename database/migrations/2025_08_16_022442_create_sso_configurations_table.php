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
        Schema::create('sso_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Configuration name (e.g., "University SAML", "Google OAuth")
            $table->string('provider'); // Provider type: saml, oauth2, oidc
            $table->string('protocol'); // Protocol: saml2, oauth2, oidc
            $table->string('institution_id')->nullable(); // For tenant-specific configs
            $table->json('configuration'); // Provider-specific configuration
            $table->json('attribute_mapping')->nullable(); // Map external attributes to user fields
            $table->json('role_mapping')->nullable(); // Map external roles to internal roles
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_provision')->default(false); // Just-in-time provisioning
            $table->boolean('auto_update')->default(false); // Auto-update user data on login
            $table->string('entity_id')->nullable(); // SAML Entity ID
            $table->text('certificate')->nullable(); // X.509 certificate for SAML
            $table->text('private_key')->nullable(); // Private key for SAML
            $table->string('sso_url')->nullable(); // SSO endpoint URL
            $table->string('sls_url')->nullable(); // Single Logout Service URL
            $table->string('client_id')->nullable(); // OAuth2/OIDC client ID
            $table->string('client_secret')->nullable(); // OAuth2/OIDC client secret
            $table->string('discovery_url')->nullable(); // OIDC discovery URL
            $table->json('scopes')->nullable(); // OAuth2/OIDC scopes
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();

            $table->index(['institution_id', 'is_active']);
            $table->index(['provider', 'is_active']);
            $table->foreign('institution_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_configurations');
    }
};
