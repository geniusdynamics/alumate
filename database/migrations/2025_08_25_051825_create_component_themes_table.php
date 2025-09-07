<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Default theme configuration structure:
     * {
     *   "colors": {
     *     "primary": "#3B82F6",
     *     "secondary": "#6B7280",
     *     "accent": "#10B981",
     *     "background": "#FFFFFF",
     *     "surface": "#F9FAFB",
     *     "text": {
     *       "primary": "#111827",
     *       "secondary": "#6B7280",
     *       "muted": "#9CA3AF"
     *     },
     *     "border": "#E5E7EB",
     *     "success": "#10B981",
     *     "warning": "#F59E0B",
     *     "error": "#EF4444"
     *   },
     *   "fonts": {
     *     "primary": "Inter, system-ui, sans-serif",
     *     "secondary": "Georgia, serif",
     *     "mono": "JetBrains Mono, monospace",
     *     "sizes": {
     *       "xs": "0.75rem",
     *       "sm": "0.875rem",
     *       "base": "1rem",
     *       "lg": "1.125rem",
     *       "xl": "1.25rem",
     *       "2xl": "1.5rem",
     *       "3xl": "1.875rem",
     *       "4xl": "2.25rem"
     *     },
     *     "weights": {
     *       "normal": "400",
     *       "medium": "500",
     *       "semibold": "600",
     *       "bold": "700"
     *     }
     *   },
     *   "spacing": {
     *     "xs": "0.25rem",
     *     "sm": "0.5rem",
     *     "md": "1rem",
     *     "lg": "1.5rem",
     *     "xl": "2rem",
     *     "2xl": "3rem",
     *     "3xl": "4rem"
     *   },
     *   "borderRadius": {
     *     "none": "0",
     *     "sm": "0.125rem",
     *     "md": "0.375rem",
     *     "lg": "0.5rem",
     *     "xl": "0.75rem",
     *     "full": "9999px"
     *   },
     *   "shadows": {
     *     "sm": "0 1px 2px 0 rgb(0 0 0 / 0.05)",
     *     "md": "0 4px 6px -1px rgb(0 0 0 / 0.1)",
     *     "lg": "0 10px 15px -3px rgb(0 0 0 / 0.1)",
     *     "xl": "0 20px 25px -5px rgb(0 0 0 / 0.1)"
     *   }
     * }
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('component_themes', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('slug');
            $table->json('config')->comment('JSON configuration for colors, fonts, spacing, and other theme properties');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Add unique constraint on (tenant_id, slug) to prevent duplicate theme names per tenant
            $table->unique(['tenant_id', 'slug'], 'component_themes_tenant_slug_unique');

            // Add foreign key constraint to tenants table with cascade delete
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Add index for performance on tenant queries
            $table->index(['tenant_id', 'is_default'], 'component_themes_tenant_default_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_themes');
    }
};
