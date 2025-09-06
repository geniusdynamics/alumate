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
        // Performance sessions table
        Schema::create('performance_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('referrer', 500)->nullable();
            $table->string('user_agent', 500);
            $table->integer('viewport_width');
            $table->integer('viewport_height');
            $table->integer('screen_width');
            $table->integer('screen_height');
            $table->integer('screen_color_depth')->nullable();
            $table->string('connection_type', 50)->nullable();
            $table->decimal('connection_downlink', 8, 2)->nullable();
            $table->integer('connection_rtt')->nullable();
            $table->boolean('connection_save_data')->default(false);
            $table->bigInteger('memory_used')->nullable();
            $table->bigInteger('memory_total')->nullable();
            $table->bigInteger('memory_limit')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Store user ID without foreign key constraint
            $table->string('tenant_id', 36);
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('url');
        });

        // Performance metrics table
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('name', 100);
            $table->decimal('value', 12, 4);
            $table->string('url', 500);
            $table->json('metadata')->nullable();
            $table->bigInteger('timestamp');
            $table->timestamps();

            // Indexes for performance analytics
            $table->index(['name', 'created_at']);
            $table->index(['session_id', 'name']);
            $table->index(['url', 'name']);
            $table->index('timestamp');
            $table->index('value');
        });

        // Performance alerts table (for threshold violations)
        Schema::create('performance_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('metric_name', 100);
            $table->decimal('metric_value', 12, 4);
            $table->decimal('threshold_value', 12, 4);
            $table->string('severity', 20); // 'warning', 'critical'
            $table->string('url', 500);
            $table->json('context')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable(); // Store user ID without foreign key constraint
            $table->timestamps();

            // Indexes
            $table->index(['metric_name', 'severity', 'created_at']);
            $table->index(['acknowledged', 'created_at']);
            $table->index('url');
        });

        // Performance budgets table (for setting performance targets)
        Schema::create('performance_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('metric_name', 100);
            $table->string('url_pattern', 500)->nullable(); // null means global
            $table->decimal('target_value', 12, 4);
            $table->decimal('warning_threshold', 12, 4);
            $table->decimal('critical_threshold', 12, 4);
            $table->boolean('is_active')->default(true);
            $table->string('tenant_id', 36);
            $table->unsignedBigInteger('created_by'); // Store user ID without foreign key constraint
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['metric_name', 'is_active']);
            $table->index('url_pattern');
        });

        // Performance reports table (for scheduled reports)
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('type', 50); // 'daily', 'weekly', 'monthly'
            $table->json('metrics'); // Array of metric names to include
            $table->json('filters')->nullable(); // URL patterns, user segments, etc.
            $table->json('recipients'); // Email addresses
            $table->string('frequency', 20); // 'daily', 'weekly', 'monthly'
            $table->time('scheduled_time')->default('09:00:00');
            $table->integer('scheduled_day')->nullable(); // Day of week (1-7) or month (1-31)
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->string('tenant_id', 36);
            $table->unsignedBigInteger('created_by'); // Store user ID without foreign key constraint
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['is_active', 'next_send_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reports');
        Schema::dropIfExists('performance_budgets');
        Schema::dropIfExists('performance_alerts');
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('performance_sessions');
    }
};