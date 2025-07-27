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
        Schema::table('activity_logs', function (Blueprint $table) {
            // Add missing columns that the LogUserActivity listener expects
            if (!Schema::hasColumn('activity_logs', 'description')) {
                $table->string('description')->after('activity');
            }
            if (!Schema::hasColumn('activity_logs', 'ip_address')) {
                $table->string('ip_address', 45)->after('description');
            }
            if (!Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('activity_logs', 'properties')) {
                $table->json('properties')->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('activity_logs', 'model_type')) {
                $table->string('model_type')->nullable()->after('properties');
            }
            if (!Schema::hasColumn('activity_logs', 'model_id')) {
                $table->unsignedBigInteger('model_id')->nullable()->after('model_type');
            }
            if (!Schema::hasColumn('activity_logs', 'session_id')) {
                $table->string('session_id')->nullable()->after('model_id');
            }
            if (!Schema::hasColumn('activity_logs', 'tenant_id')) {
                $table->string('tenant_id')->nullable()->after('session_id');
            }
            
            // Add indexes for better performance
            if (!Schema::hasIndex('activity_logs', ['user_id', 'created_at'])) {
                $table->index(['user_id', 'created_at']);
            }
            if (!Schema::hasIndex('activity_logs', ['activity', 'created_at'])) {
                $table->index(['activity', 'created_at']);
            }
            if (!Schema::hasIndex('activity_logs', ['model_type', 'model_id'])) {
                $table->index(['model_type', 'model_id']);
            }
            if (!Schema::hasIndex('activity_logs', ['ip_address', 'created_at'])) {
                $table->index(['ip_address', 'created_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['activity', 'created_at']);
            $table->dropIndex(['model_type', 'model_id']);
            $table->dropIndex(['ip_address', 'created_at']);
            
            $table->dropColumn([
                'description',
                'ip_address', 
                'user_agent',
                'properties',
                'model_type',
                'model_id',
                'session_id',
                'tenant_id'
            ]);
        });
    }
};