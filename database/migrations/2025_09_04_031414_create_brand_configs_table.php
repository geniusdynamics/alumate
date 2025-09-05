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
        Schema::create('brand_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('primary_color', 7)->nullable(); // Hex color code
            $table->string('secondary_color', 7)->nullable(); // Hex color code
            $table->string('accent_color', 7)->nullable(); // Hex color code
            $table->string('font_family')->nullable();
            $table->string('heading_font_family')->nullable();
            $table->string('body_font_family')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->text('custom_css')->nullable();
            $table->json('font_weights')->nullable(); // Available font weights
            $table->json('brand_colors')->nullable(); // Additional brand colors
            $table->json('typography_settings')->nullable();
            $table->json('spacing_settings')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('usage_guidelines')->nullable();

            // Asset management fields
            $table->json('logo_urls')->nullable(); // Multiple logo variants
            $table->json('favicon_urls')->nullable(); // Multiple favicon variants
            $table->json('asset_urls')->nullable(); // Custom assets (CSS, fonts, etc.)
            $table->string('primary_logo_id')->nullable();
            $table->string('primary_favicon_id')->nullable();

            // Tracking fields
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // Compliance and accessibility
            $table->json('accessibility_settings')->nullable();
            $table->string('wcag_compliance_level')->default('AA');

            // Additional fields for audit trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Indexes
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'usage_count']);
            $table->index(['tenant_id', 'last_used_at']);
            $table->index('name');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_configs');
    }
};
