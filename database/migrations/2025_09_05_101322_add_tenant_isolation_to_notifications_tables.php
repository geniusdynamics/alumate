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
        // Add tenant isolation to notification_preferences table
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->string('tenant_id')->after('id');
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'notification_type']);
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Add tenant isolation to notification_templates table
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->string('tenant_id')->after('id');
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'name']);
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Add tenant isolation to notification_logs table
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('tenant_id')->after('id');
            $table->index(['tenant_id', 'channel']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'sent_at']);
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Add tenant isolation to notifications table (Laravel's default)
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->after('id');
            $table->index(['tenant_id', 'notifiable_type', 'notifiable_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'read_at']);
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'read_at']);
            $table->dropIndex(['tenant_id', 'type']);
            $table->dropIndex(['tenant_id', 'notifiable_type', 'notifiable_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'sent_at']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['tenant_id', 'channel']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'name']);
            $table->dropIndex(['tenant_id', 'type']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'notification_type']);
            $table->dropIndex(['tenant_id', 'user_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
