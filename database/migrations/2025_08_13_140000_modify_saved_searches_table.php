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
        Schema::table('saved_searches', function (Blueprint $table) {
            // Change query column from text to string if needed
            $table->string('query', 500)->change();

            // Add new columns that were in the duplicate migration
            $table->boolean('email_alerts')->default(false)->after('filters');
            $table->enum('alert_frequency', ['immediate', 'daily', 'weekly'])->default('daily')->after('email_alerts');
            $table->timestamp('last_run_at')->nullable()->after('alert_frequency');
            $table->integer('last_result_count')->nullable()->after('last_run_at');

            // Drop old columns that are being replaced
            $table->dropColumn(['result_count', 'last_executed_at']);

            // Add new indexes
            $table->index(['email_alerts', 'alert_frequency']);
            $table->index('last_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            // Restore original columns
            $table->integer('result_count')->default(0)->after('filters');
            $table->timestamp('last_executed_at')->nullable()->after('result_count');

            // Drop new columns
            $table->dropColumn(['email_alerts', 'alert_frequency', 'last_run_at', 'last_result_count']);

            // Drop new indexes
            $table->dropIndex(['email_alerts', 'alert_frequency']);
            $table->dropIndex(['last_run_at']);

            // Change query back to text
            $table->text('query')->change();
        });
    }
};
