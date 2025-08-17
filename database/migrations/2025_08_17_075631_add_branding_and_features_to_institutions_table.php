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
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
            $table->string('primary_color')->nullable()->after('logo_path');
            $table->string('secondary_color')->nullable()->after('primary_color');
            $table->json('feature_flags')->nullable()->after('secondary_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'primary_color', 'secondary_color', 'feature_flags']);
        });
    }
};
