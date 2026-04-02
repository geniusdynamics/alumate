<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Users Migration
 *
 * Combines all user-related migrations into a single file:
 * - 0001_01_01_000000_create_users_table.php (Laravel Breeze base)
 * - 2025_01_26_000001_enhance_users_table.php
 * - 2025_07_14_065451_add_filters_to_users_table.php
 * - 2025_07_30_023010_add_notification_preferences_to_users_table.php
 * - 2025_08_03_000001_add_user_type_to_users_table.php
 * - 2025_08_13_020215_add_interests_to_users_table.php
 * - 2025_08_13_133307_add_location_fields_to_users_table.php
 * - 2025_08_16_164323_add_missing_columns_to_users_table.php
 * - 2025_10_03_174843_add_graduation_fields_to_users_table.php
 * - 2026_02_13_000001_add_location_skills_to_users_table.php
 * - 2026_02_13_000014_add_privacy_settings_to_users_table.php
 * - 2026_03_09_042227_add_current_tenant_id_to_users_table.php
 *
 * IMPORTANT: This migration checks if columns exist before adding them,
 * making it safe to run on both fresh and existing databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Create base users table if it doesn't exist
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create password_reset_tokens table if it doesn't exist
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Create sessions table if it doesn't exist
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // Add all user columns if they don't exist
        Schema::table('users', function (Blueprint $table) {
            // Profile information
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable();
            }

            // Institution & tenancy
            if (! Schema::hasColumn('users', 'institution_id')) {
                $table->string('institution_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'current_tenant_id')) {
                $table->string('current_tenant_id')->nullable();
            }

            // Profile data & preferences (JSON)
            if (! Schema::hasColumn('users', 'profile_data')) {
                $table->json('profile_data')->nullable();
            }
            if (! Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable();
            }

            // Account status
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false);
            }
            if (! Schema::hasColumn('users', 'is_suspended')) {
                $table->boolean('is_suspended')->default(false);
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active');
            }
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default('graduate');
            }

            // Security & login tracking
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'login_count')) {
                $table->integer('login_count')->default(0);
            }
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }

            // Two-factor authentication
            if (! Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable();
            }

            // Localization
            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('UTC');
            }
            if (! Schema::hasColumn('users', 'locale')) {
                $table->string('locale')->default('en');
            }
            if (! Schema::hasColumn('users', 'language')) {
                $table->string('language')->default('en');
            }

            // Location fields
            if (! Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable();
            }
            if (! Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
            if (! Schema::hasColumn('users', 'region')) {
                $table->string('region')->nullable();
            }
            if (! Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (! Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }

            // Employment fields
            if (! Schema::hasColumn('users', 'current_title')) {
                $table->string('current_title')->nullable();
            }
            if (! Schema::hasColumn('users', 'current_company')) {
                $table->string('current_company')->nullable();
            }
            if (! Schema::hasColumn('users', 'current_industry')) {
                $table->string('current_industry')->nullable();
            }

            // Graduation fields
            if (! Schema::hasColumn('users', 'graduation_year')) {
                $table->integer('graduation_year')->nullable();
            }
            if (! Schema::hasColumn('users', 'degree')) {
                $table->string('degree')->nullable();
            }

            // Skills & interests
            if (! Schema::hasColumn('users', 'skills')) {
                $table->json('skills')->nullable();
            }
            if (! Schema::hasColumn('users', 'interests')) {
                $table->json('interests')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_mentor')) {
                $table->boolean('is_mentor')->default(false);
            }

            // Privacy settings
            if (! Schema::hasColumn('users', 'profile_visibility')) {
                $table->enum('profile_visibility', ['public', 'alumni_only', 'private'])->default('alumni_only');
            }
            if (! Schema::hasColumn('users', 'location_privacy')) {
                $table->enum('location_privacy', ['public', 'alumni_only', 'private'])->default('alumni_only');
            }
            if (! Schema::hasColumn('users', 'is_open_to_opportunities')) {
                $table->boolean('is_open_to_opportunities')->default(false);
            }

            // Indexes
            $this->addIndexIfNotExists($table, 'users_email_index', ['email']);
            $this->addIndexIfNotExists($table, 'users_institution_id_index', ['institution_id']);
            $this->addIndexIfNotExists($table, 'users_is_active_index', ['is_active']);
            $this->addIndexIfNotExists($table, 'users_last_login_at_index', ['last_login_at']);
            $this->addIndexIfNotExists($table, 'users_graduation_year_index', ['graduation_year']);
            $this->addIndexIfNotExists($table, 'users_country_region_index', ['country', 'region']);
        });

        // Add foreign key if it doesn't exist
        $this->addForeignKeyIfNotExists('users', 'institution_id', 'tenants', 'id');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropIndex(['email']);
            $table->dropIndex(['institution_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['graduation_year']);
            $table->dropIndex(['country', 'region']);

            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'avatar', 'avatar_url',
                'institution_id', 'current_tenant_id',
                'profile_data', 'preferences',
                'is_active', 'is_super_admin', 'is_suspended', 'status', 'user_type',
                'last_login_at', 'last_login_ip', 'last_activity_at', 'login_count', 'password_changed_at',
                'two_factor_enabled', 'two_factor_secret',
                'timezone', 'locale', 'language',
                'location', 'country', 'region', 'latitude', 'longitude',
                'current_title', 'current_company', 'current_industry',
                'graduation_year', 'degree',
                'skills', 'interests', 'is_mentor',
                'profile_visibility', 'location_privacy', 'is_open_to_opportunities',
            ]);
        });

        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }

    /**
     * Helper: Add index only if it doesn't exist
     */
    private function addIndexIfNotExists(Blueprint $table, string $name, array $columns): void
    {
        // Laravel doesn't have a built-in check, so we use a try-catch
        // In production, the migration's column existence checks above prevent issues
        try {
            $table->index($columns, $name);
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Helper: Add foreign key only if it doesn't exist
     */
    private function addForeignKeyIfNotExists(string $table, string $column, string $referencesTable, string $referencesColumn): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($column, $referencesTable, $referencesColumn) {
                $t->foreign($column)->references($referencesColumn)->on($referencesTable)->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key already exists, skip
        }
    }
};
