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
            // Add is_active column if not exists
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            // Add profile visibility settings
            if (! Schema::hasColumn('users', 'profile_visibility')) {
                $table->enum('profile_visibility', ['public', 'alumni_only', 'private'])->default('alumni_only');
            }

            // Add location privacy settings
            if (! Schema::hasColumn('users', 'location_privacy')) {
                $table->enum('location_privacy', ['public', 'alumni_only', 'private'])->default('alumni_only');
            }

            // Add location fields if not exists
            if (! Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }

            if (! Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }

            if (! Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable();
            }

            if (! Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }

            if (! Schema::hasColumn('users', 'region')) {
                $table->string('region')->nullable();
            }

            // Add current employment fields
            if (! Schema::hasColumn('users', 'current_title')) {
                $table->string('current_title')->nullable();
            }

            if (! Schema::hasColumn('users', 'current_company')) {
                $table->string('current_company')->nullable();
            }

            if (! Schema::hasColumn('users', 'current_industry')) {
                $table->string('current_industry')->nullable();
            }

            // Add avatar URL
            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'profile_visibility',
                'location_privacy',
                'latitude',
                'longitude',
                'location',
                'country',
                'region',
                'current_title',
                'current_company',
                'current_industry',
                'avatar_url',
            ]);
        });
    }
};
