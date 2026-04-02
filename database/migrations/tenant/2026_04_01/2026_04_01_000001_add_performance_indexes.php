<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing indexes for performance optimization
 *
 * Based on PROJ-ANALYSIS database optimization recommendations.
 * These indexes address the most common query patterns and N+1 issues.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'users_active_created_idx');
            $table->index(['tenant_id', 'is_active'], 'users_tenant_active_idx');
            $table->index(['email', 'tenant_id'], 'users_email_tenant_idx');
            $table->index(['location_privacy', 'latitude', 'longitude'], 'users_location_privacy_idx');
            $table->index(['country', 'region'], 'users_location_idx');
            $table->index(['is_open_to_opportunities', 'is_active'], 'users_opportunities_idx');
        });

        // Graduates table indexes
        Schema::table('graduates', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_active'], 'graduates_tenant_active_idx');
            $table->index(['graduation_year', 'tenant_id'], 'graduates_year_tenant_idx');
            $table->index(['user_id', 'tenant_id'], 'graduates_user_tenant_idx');
        });

        // Connections table indexes
        Schema::table('connections', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'connections_user_status_idx');
            $table->index(['connected_user_id', 'status'], 'connections_connected_status_idx');
            $table->index(['status', 'created_at'], 'connections_status_created_idx');
        });

        // Events table indexes
        Schema::table('events', function (Blueprint $table) {
            $table->index(['tenant_id', 'start_date'], 'events_tenant_date_idx');
            $table->index(['status', 'start_date'], 'events_status_date_idx');
            $table->index(['is_featured', 'start_date'], 'events_featured_date_idx');
        });

        // Jobs table indexes
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'jobs_tenant_status_idx');
            $table->index(['status', 'created_at'], 'jobs_status_created_idx');
            $table->index(['industry', 'status'], 'jobs_industry_status_idx');
            $table->index(['is_remote', 'status'], 'jobs_remote_status_idx');
        });

        // Posts table indexes
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'posts_user_created_idx');
            $table->index(['tenant_id', 'created_at'], 'posts_tenant_created_idx');
            $table->index(['status', 'created_at'], 'posts_status_created_idx');
        });

        // Comments table indexes
        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'created_at'], 'comments_post_created_idx');
            $table->index(['user_id', 'created_at'], 'comments_user_created_idx');
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'notifications_user_read_idx');
            $table->index(['user_id', 'created_at'], 'notifications_user_created_idx');
            $table->index(['type', 'created_at'], 'notifications_type_created_idx');
        });

        // Testimonials table indexes
        Schema::table('testimonials', function (Blueprint $table) {
            $table->index(['audience_type', 'is_featured'], 'testimonials_audience_featured_idx');
            $table->index(['is_approved', 'is_featured'], 'testimonials_approved_featured_idx');
        });

        // Success stories table indexes
        Schema::table('success_stories', function (Blueprint $table) {
            $table->index(['is_published', 'is_featured'], 'stories_published_featured_idx');
            $table->index(['published_at', 'is_featured'], 'stories_published_featured_date_idx');
        });

        // Templates table indexes
        Schema::table('templates', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_active'], 'templates_tenant_active_idx');
            $table->index(['category', 'is_active'], 'templates_category_active_idx');
            $table->index(['audience_type', 'campaign_type'], 'templates_audience_campaign_idx');
        });

        // Landing pages table indexes
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'landing_pages_tenant_status_idx');
            $table->index(['status', 'published_at'], 'landing_pages_status_published_idx');
            $table->index(['slug', 'tenant_id'], 'landing_pages_slug_tenant_idx');
        });

        // Brand configs table indexes
        Schema::table('brand_configs', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_active'], 'brand_configs_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_active_created_idx');
            $table->dropIndex('users_tenant_active_idx');
            $table->dropIndex('users_email_tenant_idx');
            $table->dropIndex('users_location_privacy_idx');
            $table->dropIndex('users_location_idx');
            $table->dropIndex('users_opportunities_idx');
        });

        Schema::table('graduates', function (Blueprint $table) {
            $table->dropIndex('graduates_tenant_active_idx');
            $table->dropIndex('graduates_year_tenant_idx');
            $table->dropIndex('graduates_user_tenant_idx');
        });

        Schema::table('connections', function (Blueprint $table) {
            $table->dropIndex('connections_user_status_idx');
            $table->dropIndex('connections_connected_status_idx');
            $table->dropIndex('connections_status_created_idx');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_tenant_date_idx');
            $table->dropIndex('events_status_date_idx');
            $table->dropIndex('events_featured_date_idx');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_tenant_status_idx');
            $table->dropIndex('jobs_status_created_idx');
            $table->dropIndex('jobs_industry_status_idx');
            $table->dropIndex('jobs_remote_status_idx');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_user_created_idx');
            $table->dropIndex('posts_tenant_created_idx');
            $table->dropIndex('posts_status_created_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_created_idx');
            $table->dropIndex('comments_user_created_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_idx');
            $table->dropIndex('notifications_user_created_idx');
            $table->dropIndex('notifications_type_created_idx');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropIndex('testimonials_audience_featured_idx');
            $table->dropIndex('testimonials_approved_featured_idx');
        });

        Schema::table('success_stories', function (Blueprint $table) {
            $table->dropIndex('stories_published_featured_idx');
            $table->dropIndex('stories_published_featured_date_idx');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropIndex('templates_tenant_active_idx');
            $table->dropIndex('templates_category_active_idx');
            $table->dropIndex('templates_audience_campaign_idx');
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropIndex('landing_pages_tenant_status_idx');
            $table->dropIndex('landing_pages_status_published_idx');
            $table->dropIndex('landing_pages_slug_tenant_idx');
        });

        Schema::table('brand_configs', function (Blueprint $table) {
            $table->dropIndex('brand_configs_tenant_active_idx');
        });
    }
};
