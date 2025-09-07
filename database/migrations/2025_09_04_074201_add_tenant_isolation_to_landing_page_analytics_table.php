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
        Schema::table('landing_page_analytics', function (Blueprint $table) {
            $table->foreignUlid('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->boolean('is_compliant')->default(true)->after('city');

            // Add template relationship for performance tracking
            $table->foreignId('template_id')->nullable()->after('landing_page_id')->constrained()->onDelete('set null');

            // Enhanced privacy controls
            $table->boolean('consent_given')->default(true)->after('is_compliant');
            $table->timestamp('data_retention_until')->nullable()->after('consent_given');
            $table->string('analytics_version', 10)->default('v1.0')->after('data_retention_until');

            // Additional indices for tenant isolation and performance
            $table->index(['tenant_id', 'landing_page_id', 'event_time'], 'lp_analytics_tenant_page_time_idx');
            $table->index(['tenant_id', 'template_id', 'event_time'], 'lp_analytics_tenant_template_time_idx');
            $table->index(['tenant_id', 'session_id', 'event_time'], 'lp_analytics_tenant_session_time_idx');
            $table->index(['tenant_id', 'event_type', 'is_compliant'], 'lp_analytics_tenant_type_compliant_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_page_analytics', function (Blueprint $table) {
            $table->dropIndex('lp_analytics_tenant_page_time_idx');
            $table->dropIndex('lp_analytics_tenant_template_time_idx');
            $table->dropIndex('lp_analytics_tenant_session_time_idx');
            $table->dropIndex('lp_analytics_tenant_type_compliant_idx');

            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn([
                'tenant_id',
                'is_compliant',
                'consent_given',
                'data_retention_until',
                'analytics_version',
            ]);
        });
    }
};