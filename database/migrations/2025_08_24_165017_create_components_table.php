<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Components Table Migration
 *
 * This migration creates the components table for the Component Library System.
 * Components are reusable UI elements that can be customized and used across
 * different pages within a tenant's environment.
 *
 * VALIDATION RULES (for reference in Form Requests):
 *
 * - tenant_id: required|exists:tenants,id
 * - name: required|string|max:255|min:3
 * - slug: required|string|max:255|regex:/^[a-z0-9-]+$/|unique:components,slug,{id},id,tenant_id,{tenant_id}
 * - category: required|in:hero,forms,testimonials,statistics,ctas,media
 * - type: required|string|max:100|min:2
 * - description: nullable|string|max:1000
 * - config: required|json|min:2 (must be valid JSON object, not array or primitive)
 * - metadata: nullable|json
 * - version: required|string|regex:/^\d+\.\d+\.\d+$/
 * - is_active: required|boolean
 *
 * CONFIG SCHEMA VALIDATION:
 * - Must contain at least: {"schema": {...}, "defaults": {...}}
 * - Schema defines available configuration options with types and validation
 * - Defaults provide fallback values for all schema properties
 *
 * BUSINESS RULES:
 * - Components are tenant-scoped (multi-tenant isolation)
 * - Slug must be unique within tenant scope
 * - Category determines available configuration schema
 * - Inactive components cannot be used in new instances
 * - Version follows semantic versioning (major.minor.patch)
 */

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();

            // Tenant relationship for multi-tenancy
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Component identification
            $table->string('name')
                ->comment('Human-readable component name');
            $table->string('slug')
                ->comment('URL-friendly component identifier');

            // Component categorization
            $table->enum('category', [
                'hero',
                'forms',
                'testimonials',
                'statistics',
                'ctas',
                'media',
            ])->comment('Component category for organization and filtering');

            $table->string('type')
                ->comment('Specific component type within category (e.g., carousel, grid, single)');

            // Component metadata
            $table->text('description')->nullable()
                ->comment('Component description for admin interface');

            // Configuration and metadata as JSON
            $table->json('config')
                ->comment('Component configuration schema and default values - must be valid JSON object');
            $table->json('metadata')->nullable()
                ->comment('Additional component metadata (tags, usage stats, etc.) - must be valid JSON object');

            // Versioning and status
            $table->string('version')->default('1.0.0')
                ->comment('Component version for tracking changes and compatibility');
            $table->boolean('is_active')->default(true)
                ->comment('Whether component is available for use');

            $table->timestamps();

            // Indexes for query performance
            $table->index('tenant_id', 'idx_components_tenant_id');
            $table->index('category', 'idx_components_category');
            $table->index('is_active', 'idx_components_is_active');
            $table->index(['tenant_id', 'category'], 'idx_components_tenant_category');
            $table->index(['tenant_id', 'is_active'], 'idx_components_tenant_active');

            // Unique constraint for slug within tenant
            $table->unique(['tenant_id', 'slug'], 'unique_components_tenant_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
