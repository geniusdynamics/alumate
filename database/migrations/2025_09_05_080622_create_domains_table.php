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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('domain_name')->unique();
            $table->string('domain_type')->default('custom'); // custom, subdomain, tenant
            $table->string('status')->default('pending'); // pending, verifying, verified, failed
            $table->boolean('is_primary')->default(false);
            $table->boolean('ssl_enabled')->default(false);
            $table->string('ssl_certificate_id')->nullable();
            $table->json('dns_records')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->text('verification_error')->nullable();
            $table->json('domain_config')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['domain_name']);
            $table->index(['status', 'verified_at']);
            $table->index(['is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
