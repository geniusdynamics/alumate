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
        Schema::table('template_analytics_events', function (Blueprint $table) {
            // GDPR compliance fields
            $table->boolean('is_compliant')->default(true)->after('ip_address');
            $table->boolean('consent_given')->default(true)->after('is_compliant');
            $table->timestamp('data_retention_until')->nullable()->after('consent_given');
            $table->string('analytics_version', 10)->default('v1.0')->after('data_retention_until');

            // Add indexes for GDPR compliance queries
            $table->index(['tenant_id', 'is_compliant'], 'template_analytics_gdpr_compliant_idx');
            $table->index(['tenant_id', 'consent_given'], 'template_analytics_gdpr_consent_idx');
            $table->index(['tenant_id', 'data_retention_until'], 'template_analytics_gdpr_retention_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_analytics_events', function (Blueprint $table) {
            $table->dropIndex('template_analytics_gdpr_compliant_idx');
            $table->dropIndex('template_analytics_gdpr_consent_idx');
            $table->dropIndex('template_analytics_gdpr_retention_idx');

            $table->dropColumn([
                'is_compliant',
                'consent_given',
                'data_retention_until',
                'analytics_version',
            ]);
        });
    }
};
