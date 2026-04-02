<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create User Decomposition Tables
 *
 * Extracts profile, preferences, and academic data from the monolithic
 * users table into focused, normalized tables.
 *
 * The users table retains only authentication-critical fields:
 * - id, name, email, email_verified_at, password, remember_token
 * - is_active, is_super_admin, is_suspended
 * - last_login_at, last_login_ip, login_count
 * - two_factor_enabled, two_factor_secret, password_changed_at
 * - current_tenant_id, institution_id (tenancy)
 * - timestamps, soft_deletes
 *
 * New tables:
 * - user_profiles: Personal info, location, employment, visibility
 * - user_preferences: Settings, notifications, appearance, privacy
 * - user_academic_records: Graduation, degree, skills, mentorship
 */
return new class extends Migration
{
    public function up(): void
    {
        // User Profiles table
        if (! Schema::hasTable('user_profiles')) {
            Schema::create('user_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('avatar')->nullable();
                $table->string('avatar_url')->nullable();
                $table->string('location')->nullable();
                $table->string('country')->nullable();
                $table->string('region')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('current_title')->nullable();
                $table->string('current_company')->nullable();
                $table->string('current_industry')->nullable();
                $table->text('bio')->nullable();
                $table->string('website')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->string('twitter_url')->nullable();
                $table->string('github_url')->nullable();
                $table->enum('profile_visibility', ['public', 'alumni_only', 'private'])->default('alumni_only');
                $table->enum('location_privacy', ['public', 'alumni_only', 'private'])->default('alumni_only');
                $table->boolean('is_open_to_opportunities')->default(false);
                $table->timestamps();

                $table->unique('user_id');
                $table->index(['country', 'region']);
                $table->index(['current_company']);
                $table->index(['is_open_to_opportunities']);
            });
        }

        // User Preferences table
        if (! Schema::hasTable('user_preferences')) {
            Schema::create('user_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('preferences')->nullable();
                $table->json('notification_preferences')->nullable();
                $table->json('appearance_preferences')->nullable();
                $table->json('privacy_preferences')->nullable();
                $table->json('email_preferences')->nullable();
                $table->string('timezone')->default('UTC');
                $table->string('locale')->default('en');
                $table->string('language')->default('en');
                $table->timestamps();

                $table->unique('user_id');
                $table->index(['timezone']);
                $table->index(['locale']);
            });
        }

        // User Academic Records table
        if (! Schema::hasTable('user_academic_records')) {
            Schema::create('user_academic_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('graduation_year')->nullable();
                $table->string('degree')->nullable();
                $table->string('institution_name')->nullable();
                $table->string('major')->nullable();
                $table->string('minor')->nullable();
                $table->decimal('gpa', 4, 2)->nullable();
                $table->string('honors')->nullable();
                $table->string('thesis_title')->nullable();
                $table->json('activities')->nullable();
                $table->json('skills')->nullable();
                $table->json('certifications')->nullable();
                $table->boolean('is_mentor')->default(false);
                $table->string('mentor_specialization')->nullable();
                $table->json('mentor_availability')->nullable();
                $table->timestamps();

                $table->unique('user_id');
                $table->index(['graduation_year']);
                $table->index(['degree']);
                $table->index(['is_mentor']);
            });
        }

        // Migrate existing data from users table to new tables
        if (Schema::hasTable('users')) {
            // Migrate profile data
            $this->migrateData('users', 'user_profiles', [
                'first_name', 'last_name', 'phone', 'avatar', 'location',
                'country', 'region', 'latitude', 'longitude',
                'current_title', 'current_company', 'current_industry',
                'profile_visibility', 'location_privacy', 'is_open_to_opportunities',
            ]);

            // Migrate preferences data
            $this->migrateData('users', 'user_preferences', [
                'preferences', 'timezone', 'locale', 'language',
            ]);

            // Migrate academic data
            $this->migrateData('users', 'user_academic_records', [
                'graduation_year', 'degree', 'skills', 'is_mentor',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_academic_records');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_profiles');
    }

    /**
     * Migrate data from users table to new table
     */
    private function migrateData(string $sourceTable, string $targetTable, array $columns): void
    {
        try {
            $users = \Illuminate\Support\Facades\DB::table($sourceTable)->get();

            foreach ($users as $user) {
                $data = ['user_id' => $user->id];
                foreach ($columns as $column) {
                    if (isset($user->{$column})) {
                        $data[$column] = $user->{$column};
                    }
                }

                // Only insert if at least one column has data
                $hasData = false;
                foreach ($columns as $column) {
                    if (! empty($data[$column])) {
                        $hasData = true;
                        break;
                    }
                }

                if ($hasData) {
                    \Illuminate\Support\Facades\DB::table($targetTable)->insert($data);
                }
            }
        } catch (\Exception $e) {
            // If migration fails (e.g., columns don't exist), skip gracefully
            \Illuminate\Support\Facades\Log::warning("Data migration from {$sourceTable} to {$targetTable} skipped: ".$e->getMessage());
        }
    }
};
