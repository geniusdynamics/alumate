<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Enhanced activity logs table (create only if it doesn't exist)
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('action');
                $table->string('description');
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->json('properties')->nullable();
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('session_id')->nullable();
                $table->string('tenant_id')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['action', 'created_at']);
                $table->index(['model_type', 'model_id']);
                $table->index(['ip_address', 'created_at']);
            });
        } else {
            // If table exists, add any missing columns
            Schema::table('activity_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('activity_logs', 'session_id')) {
                    $table->string('session_id')->nullable()->after('model_id');
                }
                if (! Schema::hasColumn('activity_logs', 'tenant_id')) {
                    $table->string('tenant_id')->nullable()->after('session_id');
                }
            });
        }

        // Security events table for threat detection
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // failed_login, suspicious_activity, rate_limit_exceeded, etc.
            $table->string('severity'); // low, medium, high, critical
            $table->string('ip_address', 45);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['severity', 'resolved']);
            $table->index(['ip_address', 'created_at']);
        });

        // Data access logs for compliance
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('resource_type'); // graduate, job, application, etc.
            $table->unsignedBigInteger('resource_id');
            $table->string('access_type'); // view, create, update, delete, export
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('query_parameters')->nullable();
            $table->boolean('authorized')->default(true);
            $table->string('authorization_method')->nullable(); // role, permission, policy
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['access_type', 'created_at']);
            $table->index(['authorized', 'created_at']);
        });

        // Failed login attempts for security monitoring
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->integer('attempts')->default(1);
            $table->timestamp('last_attempt_at');
            $table->timestamp('blocked_until')->nullable();
            $table->timestamps();

            $table->index(['email', 'ip_address']);
            $table->index(['ip_address', 'last_attempt_at']);
        });

        // Session security tracking
        Schema::create('session_security', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestamp('last_activity');
            $table->boolean('is_suspicious')->default(false);
            $table->json('security_flags')->nullable(); // location_change, device_change, etc.
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'last_activity']);
            $table->index(['is_suspicious', 'last_activity']);
        });

        // Two-factor authentication
        Schema::create('two_factor_auth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(false);
            $table->string('secret')->nullable();
            $table->json('recovery_codes')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->string('backup_method')->nullable(); // email, sms
            $table->string('backup_contact')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        // Backup and recovery logs
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_type'); // full, incremental, differential
            $table->string('status'); // started, completed, failed
            $table->string('file_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['backup_type', 'status']);
            $table->index(['started_at', 'status']);
        });

        // System health monitoring
        Schema::create('system_health_logs', function (Blueprint $table) {
            $table->id();
            $table->string('component'); // database, cache, storage, etc.
            $table->string('status'); // healthy, warning, critical
            $table->json('metrics'); // response_time, memory_usage, etc.
            $table->text('message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['component', 'status']);
            $table->index(['checked_at', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_health_logs');
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('two_factor_auth');
        Schema::dropIfExists('session_security');
        Schema::dropIfExists('failed_login_attempts');
        Schema::dropIfExists('data_access_logs');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('activity_logs');
    }
};
