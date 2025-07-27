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
        // Create saved_searches table
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('search_type', ['jobs', 'graduates', 'courses']);
            $table->json('search_criteria');
            $table->boolean('alert_enabled')->default(false);
            $table->enum('alert_frequency', ['immediate', 'daily', 'weekly'])->default('weekly');
            $table->timestamp('last_alert_sent')->nullable();
            $table->integer('results_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'search_type']);
            $table->index(['alert_enabled', 'is_active']);
            $table->index('last_alert_sent');
        });

        // Create search_alerts table
        Schema::create('search_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_search_id')->constrained()->onDelete('cascade');
            $table->integer('results_count');
            $table->timestamp('sent_at');
            $table->json('results_data');
            $table->timestamp('opened_at')->nullable();
            $table->json('clicked_results')->nullable();
            $table->timestamps();

            $table->index(['saved_search_id', 'sent_at']);
            $table->index('opened_at');
        });

        // Create search_analytics table for tracking search patterns
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('search_type');
            $table->json('search_criteria');
            $table->integer('results_count');
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('searched_at');
            $table->timestamps();

            $table->index(['user_id', 'searched_at']);
            $table->index(['search_type', 'searched_at']);
        });

        // Add search-related columns to existing tables
        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('search_views')->default(0)->after('view_count');
            $table->integer('search_applications')->default(0)->after('search_views');
            $table->decimal('avg_match_score', 5, 2)->nullable()->after('match_score');
            $table->json('popular_search_terms')->nullable()->after('avg_match_score');
            
            $table->index('search_views');
            $table->index('avg_match_score');
        });

        // Note: Graduate table modifications are handled in tenant migrations
        // since graduates table only exists in tenant databases

        // Note: job_graduate_matches table is created in tenant migrations
        // since it references graduates table which only exists in tenant databases

        // Create search_filters table for storing common filter combinations
        Schema::create('search_filters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('search_type');
            $table->json('filter_criteria');
            $table->integer('usage_count')->default(0);
            $table->boolean('is_system_filter')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['search_type', 'is_active']);
            $table->index(['is_system_filter', 'usage_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_filters');
        // Note: job_graduate_matches table is dropped in tenant migrations
        
        // Note: Graduate table rollback is handled in tenant migrations

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['search_views']);
            $table->dropIndex(['avg_match_score']);
            $table->dropColumn(['search_views', 'search_applications', 'avg_match_score', 'popular_search_terms']);
        });

        Schema::dropIfExists('search_analytics');
        Schema::dropIfExists('search_alerts');
        Schema::dropIfExists('saved_searches');
    }
};