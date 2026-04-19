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
        Schema::table('learning_progress', function (Blueprint $table) {
            $table->unsignedBigInteger('module_id')->nullable()->after('course_id');
            $table->decimal('progress_percentage', 5, 2)->default(0)->after('module_id');
            $table->integer('engagement_duration')->default(0)->after('progress_percentage'); // minutes
            $table->timestamp('completion_timestamp')->nullable()->after('engagement_duration');
            $table->json('certifications')->nullable()->after('completion_timestamp');
            $table->integer('interactions_count')->default(0)->after('certifications');

            // Add indexes for performance
            $table->index(['module_id']);
            $table->index(['progress_percentage']);
            $table->index(['engagement_duration']);
            $table->index(['completion_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_progress', function (Blueprint $table) {
            $table->dropColumn([
                'module_id',
                'progress_percentage',
                'engagement_duration',
                'completion_timestamp',
                'certifications',
                'interactions_count'
            ]);
        });
    }
};