<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Analytics snapshots for historical data
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'daily', 'weekly', 'monthly'
            $table->date('snapshot_date');
            $table->json('data'); // Stores the analytics data
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->index(['type', 'snapshot_date']);
        });

        // Custom reports configuration
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // 'employment', 'course_performance', 'job_market', etc.
            $table->json('filters'); // Report filters configuration
            $table->json('columns'); // Selected columns/fields
            $table->json('chart_config')->nullable(); // Chart configuration
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // 'daily', 'weekly', 'monthly'
            $table->json('schedule_config')->nullable(); // Schedule configuration
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        // Report executions and results
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed'
            $table->json('parameters')->nullable(); // Runtime parameters
            $table->json('result_data')->nullable(); // Generated report data
            $table->string('file_path')->nullable(); // Path to generated file
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // KPI definitions and tracking
        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique(); // Unique identifier
            $table->text('description');
            $table->string('category'); // 'employment', 'academic', 'operational'
            $table->string('calculation_method'); // 'percentage', 'count', 'average', 'ratio'
            $table->json('calculation_config'); // Configuration for calculation
            $table->string('target_type')->nullable(); // 'minimum', 'maximum', 'range'
            $table->decimal('target_value', 10, 2)->nullable();
            $table->decimal('warning_threshold', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // KPI values tracking
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_definition_id')->constrained()->onDelete('cascade');
            $table->date('measurement_date');
            $table->decimal('value', 15, 4);
            $table->json('breakdown')->nullable(); // Detailed breakdown of the value
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->unique(['kpi_definition_id', 'measurement_date']);
            $table->index(['measurement_date', 'value']);
        });

        // Predictive analytics models and predictions
        Schema::create('prediction_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'job_placement', 'employment_success', 'course_demand'
            $table->text('description');
            $table->json('features'); // Input features for the model
            $table->json('model_config'); // Model configuration and parameters
            $table->decimal('accuracy', 5, 4)->nullable(); // Model accuracy score
            $table->timestamp('last_trained_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Prediction results
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prediction_model_id')->constrained()->onDelete('cascade');
            $table->string('subject_type'); // 'graduate', 'course', 'job'
            $table->unsignedBigInteger('subject_id');
            $table->decimal('prediction_score', 5, 4); // Prediction confidence score
            $table->json('prediction_data'); // Detailed prediction results
            $table->json('input_features'); // Features used for this prediction
            $table->date('prediction_date');
            $table->date('target_date')->nullable(); // When the prediction is for
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['prediction_date', 'prediction_score']);
        });

        // Dashboard widgets configuration
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('widget_type'); // 'chart', 'metric', 'table', 'alert'
            $table->string('title');
            $table->json('configuration'); // Widget-specific configuration
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(4);
            $table->integer('height')->default(3);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        // Data export logs
        Schema::create('data_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('export_type'); // 'report', 'analytics', 'raw_data'
            $table->string('format'); // 'csv', 'excel', 'pdf', 'json'
            $table->json('filters')->nullable(); // Export filters
            $table->string('file_path');
            $table->integer('record_count')->default(0);
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Analytics alerts and notifications
        Schema::create('analytics_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('alert_type'); // 'kpi_threshold', 'trend_change', 'anomaly'
            $table->string('title');
            $table->text('description');
            $table->string('severity'); // 'info', 'warning', 'critical'
            $table->json('trigger_conditions'); // Conditions that triggered the alert
            $table->json('alert_data'); // Data related to the alert
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('triggered_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('analytics_alerts');
        Schema::dropIfExists('data_exports');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('predictions');
        Schema::dropIfExists('prediction_models');
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('custom_reports');
        Schema::dropIfExists('analytics_snapshots');
    }
};
