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
        // Analytics events tracking table
        if (! Schema::hasTable('analytics_events')) {
            Schema::create('analytics_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type'); // page_view, feature_usage, user_action, etc.
                $table->string('event_name'); // specific event name
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->json('properties')->nullable(); // event-specific data
                $table->string('session_id')->nullable();
                $table->string('user_agent')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('referrer')->nullable();
                $table->string('page_url')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['event_type', 'occurred_at']);
                $table->index(['user_id', 'occurred_at']);
                $table->index(['session_id', 'occurred_at']);
            });
        }

        // User activity sessions table
        if (! Schema::hasTable('user_activity_sessions')) {
            Schema::create('user_activity_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->unique();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->integer('page_views')->default(0);
                $table->integer('actions_count')->default(0);
                $table->string('device_type')->nullable(); // desktop, mobile, tablet
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('referrer')->nullable();
                $table->string('landing_page')->nullable();
                $table->string('exit_page')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'started_at']);
                $table->index(['started_at']);
            });
        }

        // Feature usage tracking table
        if (! Schema::hasTable('feature_usage_tracking')) {
            Schema::create('feature_usage_tracking', function (Blueprint $table) {
                $table->id();
                $table->string('feature_name'); // timeline, directory, jobs, etc.
                $table->string('action'); // view, click, create, update, etc.
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->json('context')->nullable(); // additional context data
                $table->timestamp('used_at');
                $table->timestamps();

                $table->index(['feature_name', 'used_at']);
                $table->index(['user_id', 'used_at']);
            });
        }

        // User engagement metrics table
        if (! Schema::hasTable('user_engagement_metrics')) {
            Schema::create('user_engagement_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->integer('posts_created')->default(0);
                $table->integer('posts_liked')->default(0);
                $table->integer('comments_made')->default(0);
                $table->integer('connections_made')->default(0);
                $table->integer('profile_views')->default(0);
                $table->integer('job_views')->default(0);
                $table->integer('event_views')->default(0);
                $table->integer('session_duration_minutes')->default(0);
                $table->integer('page_views')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'date']);
                $table->index(['date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_engagement_metrics');
        Schema::dropIfExists('feature_usage_tracking');
        Schema::dropIfExists('user_activity_sessions');
        Schema::dropIfExists('analytics_events');
    }
};
