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
            $table->foreignId('tenant_id')->after('id')->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'notification_type']);
        });

        // Add tenant isolation to notification_templates table
        Schema::table('notification_templates', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'name']);
        });

        // Add tenant isolation to notification_logs table
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'channel']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'sent_at']);
        });

        // Add tenant isolation to notifications table (Laravel's default)
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index(['tenant_id', 'notifiable_type', 'notifiable_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'read_at']);
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
