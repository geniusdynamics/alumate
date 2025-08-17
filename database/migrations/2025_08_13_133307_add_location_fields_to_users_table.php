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
        Schema::table('users', function (Blueprint $table) {
            // Only add fields that don't exist (location already exists)
            $table->decimal('latitude', 10, 8)->nullable()->after('location');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('country')->nullable()->after('longitude');
            $table->string('region')->nullable()->after('country');
            $table->enum('location_privacy', ['public', 'alumni_only', 'private'])
                  ->default('alumni_only')
                  ->after('region');
            $table->timestamp('location_updated_at')->nullable()->after('location_privacy');
            
            // Add indexes for location-based queries
            $table->index(['latitude', 'longitude']);
            $table->index(['country']);
            $table->index(['region']);
            $table->index(['location_privacy']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['country']);
            $table->dropIndex(['region']);
            $table->dropIndex(['location_privacy']);
            
            $table->dropColumn([
                'latitude',
                'longitude',
                'country',
                'region',
                'location_privacy',
                'location_updated_at'
            ]);
        });
    }
};
