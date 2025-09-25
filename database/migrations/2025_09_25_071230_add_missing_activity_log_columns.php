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
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('activity_logs', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('activity_logs', 'course_id')) {
                $table->unsignedBigInteger('course_id')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('activity_logs', 'enrollment_id')) {
                $table->unsignedBigInteger('enrollment_id')->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('activity_logs', 'grade_id')) {
                $table->unsignedBigInteger('grade_id')->nullable()->after('enrollment_id');
            }
            if (!Schema::hasColumn('activity_logs', 'loggable_type')) {
                $table->string('loggable_type')->nullable()->after('grade_id');
            }
            if (!Schema::hasColumn('activity_logs', 'loggable_id')) {
                $table->unsignedBigInteger('loggable_id')->nullable()->after('loggable_type');
            }
            if (!Schema::hasColumn('activity_logs', 'category')) {
                $table->string('category')->nullable()->after('action');
            }
            if (!Schema::hasColumn('activity_logs', 'request_id')) {
                $table->string('request_id')->nullable()->after('session_id');
            }
            if (!Schema::hasColumn('activity_logs', 'severity')) {
                $table->string('severity')->default('low')->after('request_id');
            }
            if (!Schema::hasColumn('activity_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('severity');
            }
            if (!Schema::hasColumn('activity_logs', 'old_values')) {
                $table->json('old_values')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('activity_logs', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }
            if (!Schema::hasColumn('activity_logs', 'performed_at')) {
                $table->timestamp('performed_at')->nullable()->after('new_values');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $columnsToRemove = [
                'student_id', 'course_id', 'enrollment_id', 'grade_id',
                'loggable_type', 'loggable_id', 'category', 'request_id',
                'severity', 'metadata', 'old_values', 'new_values', 'performed_at'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('activity_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
