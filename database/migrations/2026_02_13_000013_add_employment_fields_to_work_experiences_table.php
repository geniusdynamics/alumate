<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            if (!Schema::hasColumn('work_experiences', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('industry');
            }
            if (!Schema::hasColumn('work_experiences', 'skills_used')) {
                $table->json('skills_used')->nullable()->after('description');
            }
            if (!Schema::hasColumn('work_experiences', 'achievements')) {
                $table->json('achievements')->nullable()->after('skills_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('work_experiences', 'employment_type')) {
                $columnsToDrop[] = 'employment_type';
            }
            if (Schema::hasColumn('work_experiences', 'skills_used')) {
                $columnsToDrop[] = 'skills_used';
            }
            if (Schema::hasColumn('work_experiences', 'achievements')) {
                $columnsToDrop[] = 'achievements';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
