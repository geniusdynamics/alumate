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
        // Users table optimizations
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'tenant_id')) {
                    $table->index(['tenant_id', 'created_at'], 'users_tenant_created_idx');
                    $table->index(['tenant_id', 'status'], 'users_tenant_status_idx');
                }
                if (Schema::hasColumn('users', 'last_activity_at')) {
                    $table->index('last_activity_at', 'users_last_activity_idx');
                }
            });
        }

        // Jobs table optimizations
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if (Schema::hasColumn('jobs', 'tenant_id')) {
                    $table->index(['tenant_id', 'status', 'created_at'], 'jobs_tenant_status_created_idx');
                }
                $table->index('employer_id', 'jobs_employer_id_idx');
            });
        }

        // Graduates table optimizations (some may be tenant-specific, but checking here)
        if (Schema::hasTable('graduates')) {
            Schema::table('graduates', function (Blueprint $table) {
                if (Schema::hasColumn('graduates', 'employment_status')) {
                    $table->index('employment_status', 'graduates_employment_status_idx');
                }
                if (Schema::hasColumn('graduates', 'course_id')) {
                    $table->index('course_id', 'graduates_course_id_idx');
                }
            });
        }

        // Connections table optimizations
        if (Schema::hasTable('connections')) {
            Schema::table('connections', function (Blueprint $table) {
                if (Schema::hasColumn('connections', 'status') && Schema::hasColumn('connections', 'connected_at')) {
                    $table->index(['status', 'connected_at'], 'connections_status_connected_idx');
                }
            });
        }

        // Posts table optimizations
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'tenant_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->index(['tenant_id', 'created_at'], 'posts_tenant_created_idx');
                $table->index(['tenant_id', 'user_id'], 'posts_tenant_user_idx');
            });
        }

        // Job Applications table optimizations
        if (Schema::hasTable('job_applications') && Schema::hasColumn('job_applications', 'tenant_id')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->index(['tenant_id', 'job_id', 'status'], 'job_apps_tenant_job_status_idx');
            });
        }
        
        // Analytics Events optimizations
        if (Schema::hasTable('analytics_events') && Schema::hasColumn('analytics_events', 'tenant_id')) {
            Schema::table('analytics_events', function (Blueprint $table) {
                $table->index(['tenant_id', 'event_type', 'created_at'], 'analytics_events_tenant_type_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_tenant_created_idx');
                $table->dropIndex('users_tenant_status_idx');
                if (Schema::hasColumn('users', 'user_type')) {
                    $table->dropIndex('users_tenant_type_idx');
                }
            });
        }

        if (Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'tenant_id')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex('jobs_tenant_status_created_idx');
            });
        }

        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'tenant_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropIndex('posts_tenant_created_idx');
                $table->dropIndex('posts_tenant_user_idx');
            });
        }

        if (Schema::hasTable('job_applications') && Schema::hasColumn('job_applications', 'tenant_id')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->dropIndex('job_apps_tenant_job_status_idx');
            });
        }

        if (Schema::hasTable('analytics_events') && Schema::hasColumn('analytics_events', 'tenant_id')) {
            Schema::table('analytics_events', function (Blueprint $table) {
                $table->dropIndex('analytics_events_tenant_type_date_idx');
            });
        }
    }
};