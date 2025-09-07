<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testimonials Table Migration
 *
 * This migration creates the testimonials table for the Component Library System.
 * Testimonials are user-generated content that can be filtered, moderated, and
 * displayed across different components with performance tracking.
 *
 * VALIDATION RULES (for reference in Form Requests):
 *
 * - tenant_id: required|exists:tenants,id
 * - author_name: required|string|max:255|min:2
 * - author_title: nullable|string|max:255
 * - author_company: nullable|string|max:255
 * - author_photo: nullable|string|max:500|url
 * - graduation_year: nullable|integer|min:1900|max:2100
 * - industry: nullable|string|max:100
 * - audience_type: required|in:individual,institution,employer
 * - content: required|string|min:10|max:2000
 * - video_url: nullable|string|max:500|url
 * - video_thumbnail: nullable|string|max:500|url
 * - rating: nullable|integer|min:1|max:5
 * - status: required|in:pending,approved,rejected,archived
 * - featured: required|boolean
 * - metadata: nullable|json
 *
 * BUSINESS RULES:
 * - Testimonials are tenant-scoped (multi-tenant isolation)
 * - Only approved testimonials can be displayed publicly
 * - Featured testimonials get priority in rotation
 * - Video testimonials require both video_url and video_thumbnail
 * - Metadata can store additional filtering and tracking information
 */

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();

            // Tenant relationship for multi-tenancy
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Author information
            $table->string('author_name')
                ->comment('Full name of the testimonial author');
            $table->string('author_title')->nullable()
                ->comment('Job title or position of the author');
            $table->string('author_company')->nullable()
                ->comment('Company or organization name');
            $table->string('author_photo')->nullable()
                ->comment('URL to author profile photo');
            $table->year('graduation_year')->nullable()
                ->comment('Year of graduation for filtering');

            // Categorization and filtering
            $table->string('industry', 100)->nullable()
                ->comment('Industry category for filtering');
            $table->enum('audience_type', ['individual', 'institution', 'employer'])
                ->comment('Target audience type for testimonial');

            // Content
            $table->text('content')
                ->comment('Main testimonial text content');
            $table->string('video_url')->nullable()
                ->comment('URL to video testimonial');
            $table->string('video_thumbnail')->nullable()
                ->comment('URL to video thumbnail image');
            $table->tinyInteger('rating')->nullable()
                ->comment('Rating given by author (1-5 stars)');

            // Moderation and status
            $table->enum('status', ['pending', 'approved', 'rejected', 'archived'])
                ->default('pending')
                ->comment('Moderation status for content approval workflow');
            $table->boolean('featured')->default(false)
                ->comment('Whether testimonial is featured (priority in rotation)');

            // Performance tracking
            $table->unsignedInteger('view_count')->default(0)
                ->comment('Number of times testimonial has been viewed');
            $table->unsignedInteger('click_count')->default(0)
                ->comment('Number of times testimonial has been clicked');
            $table->decimal('conversion_rate', 5, 4)->default(0.0000)
                ->comment('Conversion rate for A/B testing (0.0000 to 1.0000)');

            // Additional metadata
            $table->json('metadata')->nullable()
                ->comment('Additional metadata for filtering and tracking');

            $table->timestamps();

            // Indexes for query performance
            $table->index('tenant_id', 'idx_testimonials_tenant_id');
            $table->index('status', 'idx_testimonials_status');
            $table->index('audience_type', 'idx_testimonials_audience_type');
            $table->index('industry', 'idx_testimonials_industry');
            $table->index('graduation_year', 'idx_testimonials_graduation_year');
            $table->index('featured', 'idx_testimonials_featured');
            $table->index(['tenant_id', 'status'], 'idx_testimonials_tenant_status');
            $table->index(['tenant_id', 'audience_type'], 'idx_testimonials_tenant_audience');
            $table->index(['status', 'featured'], 'idx_testimonials_status_featured');
            $table->index(['tenant_id', 'status', 'featured'], 'idx_testimonials_tenant_status_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};