<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Component Instances Table Migration
 *
 * This migration creates the component_instances table for the Component Library System.
 * Component instances represent specific uses of components on pages, allowing for
 * page-specific customization while maintaining the base component definition.
 *
 * VALIDATION RULES (for reference in Form Requests):
 *
 * - component_id: required|exists:components,id
 * - page_type: required|string|max:255|in:landing_page,template,homepage,about,contact
 * - page_id: required|integer|min:1
 * - position: required|integer|min:0|max:999
 * - custom_config: nullable|json
 *
 * POLYMORPHIC RELATIONSHIP:
 * - page_type + page_id creates a polymorphic relationship to various page models
 * - Supported page types: landing_page, template, homepage, about, contact
 * - This allows flexible association with different page types without rigid foreign keys
 *
 * BUSINESS RULES:
 * - Position determines display order on the page (0 = first, higher = later)
 * - Position must be unique within the same page (page_type + page_id combination)
 * - Custom config overrides component default config for this specific instance
 * - Deleting a component cascades to delete all its instances
 * - Custom config must be valid JSON object if provided
 */

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('component_instances', function (Blueprint $table) {
            $table->id();

            // Component relationship with cascade delete
            $table->foreignId('component_id')
                ->constrained('components')
                ->onDelete('cascade')
                ->comment('Reference to the base component definition');

            // Polymorphic relationship to pages
            $table->string('page_type')
                ->comment('Type of page this component is placed on (polymorphic relationship)');
            $table->unsignedBigInteger('page_id')
                ->comment('ID of the specific page this component is placed on (polymorphic relationship)');

            // Position and ordering
            $table->unsignedInteger('position')
                ->comment('Display order position on the page (0 = first, higher = later)');

            // Instance-specific configuration
            $table->json('custom_config')->nullable()
                ->comment('Page-specific configuration overrides for this component instance - must be valid JSON object');

            $table->timestamps();

            // Composite index for efficient page loading (page_type, page_id, position)
            $table->index(['page_type', 'page_id', 'position'], 'idx_component_instances_page_position');

            // Additional indexes for query performance
            $table->index('component_id', 'idx_component_instances_component_id');
            $table->index(['page_type', 'page_id'], 'idx_component_instances_page');

            // Unique constraint to prevent duplicate positions on the same page
            $table->unique(['page_type', 'page_id', 'position'], 'unique_component_instances_page_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_instances');
    }
};
