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
        Schema::table('leads', function (Blueprint $table) {
            // Routing log for tracking lead routing decisions and results
            $table->json('routing_log')->nullable()->after('behavioral_data');
            $table->string('crm_provider')->nullable()->after('crm_id');
            $table->string('routing_status')->nullable()->after('crm_provider');
            $table->timestamp('routing_failed_at')->nullable()->after('routing_status');

            // Indexes for performance
            $table->index(['routing_status']);
            $table->index(['crm_provider']);
            $table->index(['routing_failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['routing_status']);
            $table->dropIndex(['crm_provider']);
            $table->dropIndex(['routing_failed_at']);

            $table->dropColumn(['routing_log', 'crm_provider', 'routing_status', 'routing_failed_at']);
        });
    }
};
