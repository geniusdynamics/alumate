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
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->foreignUlid('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->string('event_type')->after('event_name'); // General event category
            $table->boolean('is_compliant')->default(true)->after('ip_address');

            // Add composite indexes for tenant isolation
            $table->index(['tenant_id', 'event_type', 'timestamp'], 'analytics_events_tenant_type_time_idx');
            $table->index(['tenant_id', 'session_id', 'timestamp'], 'analytics_events_tenant_session_time_idx');

            // Add data retention for privacy compliance
            $table->timestamp('data_retention_until')->nullable();
            $table->boolean('consent_given')->default(true);
            $table->string('analytics_version', 10)->default('v1.0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex('analytics_events_tenant_type_time_idx');
            $table->dropIndex('analytics_events_tenant_session_time_idx');
            $table->dropColumn([
                'tenant_id',
                'event_type',
                'is_compliant',
                'data_retention_until',
                'consent_given',
                'analytics_version',
            ]);
        });
    }
};