<?php

declare(strict_types=1);

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
        // Add composite indexes to analytics_events table for performance optimization
        Schema::table('analytics_events', function (Blueprint $table) {
            // Composite index for tenant-scoped queries with time filtering
            $table->index(['tenant_id', 'created_at', 'event_name'], 'analytics_events_tenant_created_event_idx');

            // Composite index for session-specific analytics queries
            $table->index(['tenant_id', 'session_id', 'created_at'], 'analytics_events_tenant_session_created_idx');

            // Composite index for gamification points queries (if points_earned column exists)
            if (Schema::hasColumn('analytics_events', 'points_earned')) {
                $table->index(['tenant_id', 'event_name', 'points_earned'], 'analytics_events_tenant_gamification_points_idx');
            }
        });

        // Add indexes to heat_map_data table for performance optimization (if table exists)
        if (Schema::hasTable('heat_map_data')) {
            Schema::table('heat_map_data', function (Blueprint $table) {
                // Composite index for tenant and session queries
                $table->index(['tenant_id', 'session_id'], 'heat_map_data_tenant_session_idx');

                // Composite index for tenant and timestamp queries
                $table->index(['tenant_id', 'timestamp'], 'heat_map_data_tenant_timestamp_idx');

                // Composite index for page URL queries within tenant
                $table->index(['tenant_id', 'page_url'], 'heat_map_data_tenant_page_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex('analytics_events_tenant_created_event_idx');
            $table->dropIndex('analytics_events_tenant_session_created_idx');
            if (Schema::hasColumn('analytics_events', 'points_earned')) {
                $table->dropIndex('analytics_events_tenant_gamification_points_idx');
            }
        });

        if (Schema::hasTable('heat_map_data')) {
            Schema::table('heat_map_data', function (Blueprint $table) {
                $table->dropIndex('heat_map_data_tenant_session_idx');
                $table->dropIndex('heat_map_data_tenant_timestamp_idx');
                $table->dropIndex('heat_map_data_tenant_page_idx');
            });
        }
    }
};