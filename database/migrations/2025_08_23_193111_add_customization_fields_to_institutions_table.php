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
            // Add customization fields if they don't exist
            if (! Schema::hasColumn('institutions', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('logo_url');
            }
            if (! Schema::hasColumn('institutions', 'primary_color')) {
                $table->string('primary_color')->nullable()->after('banner_url');
            }
            if (! Schema::hasColumn('institutions', 'secondary_color')) {
                $table->string('secondary_color')->nullable()->after('primary_color');
            }
            if (! Schema::hasColumn('institutions', 'feature_flags')) {
                $table->json('feature_flags')->nullable()->after('settings');
            }
            if (! Schema::hasColumn('institutions', 'integration_settings')) {
                $table->json('integration_settings')->nullable()->after('feature_flags');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'primary_color',
                'secondary_color',
                'feature_flags',
                'integration_settings',
            ]);
        });
    }
};
