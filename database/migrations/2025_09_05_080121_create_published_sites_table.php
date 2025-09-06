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
        Schema::create('published_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->onDelete('cascade');
            $table->string('tenant_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();
            $table->string('subdomain')->nullable();
            $table->json('custom_domains')->nullable();
            $table->string('status')->default('draft'); // draft, published, suspended, archived
            $table->string('deployment_status')->default('pending'); // pending, deploying, deployed, failed
            $table->string('build_hash')->nullable();
            $table->string('cdn_url')->nullable();
            $table->string('static_url')->nullable();
            $table->json('build_config')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->json('seo_config')->nullable();
            $table->json('analytics_config')->nullable();
            $table->boolean('ssl_enabled')->default(false);
            $table->string('ssl_certificate_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->integer('deployment_count')->default(0);
            $table->json('deployment_history')->nullable();
            $table->boolean('is_ab_test_enabled')->default(false);
            $table->json('ab_test_config')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['landing_page_id']);
            $table->index(['domain']);
            $table->index(['subdomain']);
            $table->index(['status', 'deployment_status']);
            $table->index(['published_at']);
            
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('published_sites');
    }
};