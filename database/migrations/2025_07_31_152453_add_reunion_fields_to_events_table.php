<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Reunion-specific fields
            $table->integer('graduation_year')->nullable()->after('institution_id');
            $table->string('class_identifier')->nullable()->after('graduation_year'); // e.g., "Class of 2020", "MBA 2019"
            $table->boolean('is_reunion')->default(false)->after('class_identifier');
            $table->integer('reunion_year_milestone')->nullable()->after('is_reunion'); // 5, 10, 25, 50 year reunion
            $table->json('reunion_committees')->nullable()->after('reunion_year_milestone'); // Committee members and roles
            $table->json('memory_collection_settings')->nullable()->after('reunion_committees'); // Photo/memory sharing settings
            $table->boolean('enable_photo_sharing')->default(false)->after('memory_collection_settings');
            $table->boolean('enable_memory_wall')->default(false)->after('enable_photo_sharing');
            $table->json('anniversary_milestones')->nullable()->after('enable_memory_wall'); // Track various anniversaries
            $table->text('reunion_theme')->nullable()->after('anniversary_milestones');
            $table->json('class_statistics')->nullable()->after('reunion_theme'); // Attendance stats, demographics

            // Index for reunion queries
            $table->index(['is_reunion', 'graduation_year', 'institution_id']);
            $table->index(['reunion_year_milestone', 'graduation_year']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['is_reunion', 'graduation_year', 'institution_id']);
            $table->dropIndex(['reunion_year_milestone', 'graduation_year']);

            $table->dropColumn([
                'graduation_year',
                'class_identifier',
                'is_reunion',
                'reunion_year_milestone',
                'reunion_committees',
                'memory_collection_settings',
                'enable_photo_sharing',
                'enable_memory_wall',
                'anniversary_milestones',
                'reunion_theme',
                'class_statistics',
            ]);
        });
    }
};
