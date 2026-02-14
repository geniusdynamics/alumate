<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            if (!Schema::hasColumn('work_experiences', 'skills')) {
                $table->json('skills')->nullable()->after('achievements');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_experiences', function (Blueprint $table) {
            if (Schema::hasColumn('work_experiences', 'skills')) {
                $table->dropColumn('skills');
            }
        });
    }
};
