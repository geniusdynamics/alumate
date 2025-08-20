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
        // First, add the hired_at column
        Schema::table('job_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('job_applications', 'hired_at')) {
                $table->timestamp('hired_at')->nullable()->after('status');
                $table->index('hired_at');
            }
        });

        // Add 'hired' to the status enum if it doesn't exist, skipping for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('
                ALTER TABLE job_applications
                DROP CONSTRAINT IF EXISTS job_applications_status_check
            ');

            DB::statement("
                ALTER TABLE job_applications
                ADD CONSTRAINT job_applications_status_check
                CHECK (status IN ('pending', 'reviewing', 'interviewing', 'offered', 'accepted', 'rejected', 'withdrawn', 'hired'))
            ");
        }

        // Update existing records where status is 'accepted' to 'hired' and set hired_at
        DB::statement("
            UPDATE job_applications
            SET status = 'hired', hired_at = updated_at
            WHERE status = 'accepted'
            AND hired_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (Schema::hasColumn('job_applications', 'hired_at')) {
                $table->dropIndex(['hired_at']);
                $table->dropColumn('hired_at');
            }
        });
    }
};
