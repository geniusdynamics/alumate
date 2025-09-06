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
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('certificate_id')->unique();
            $table->string('domain_name');
            $table->string('provider')->default('letsencrypt'); // letsencrypt, custom
            $table->string('status')->default('pending'); // pending, issued, expired, revoked
            $table->text('certificate_content')->nullable();
            $table->text('private_key')->nullable();
            $table->text('certificate_chain')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('validation_data')->nullable();
            $table->boolean('auto_renewal')->default(true);
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['domain_name']);
            $table->index(['expires_at']);
            $table->index(['certificate_id']);
            
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};