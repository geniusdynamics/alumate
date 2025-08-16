<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Profile information
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('institution_id')->nullable()->after('avatar');
            $table->json('profile_data')->nullable()->after('institution_id');
            $table->json('preferences')->nullable()->after('profile_data');

            // Account status and security
            $table->string('status')->default('active')->after('preferences');
            $table->boolean('is_suspended')->default(false)->after('status');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');
            $table->string('suspension_reason')->nullable()->after('suspended_at');
            $table->timestamp('last_login_at')->nullable()->after('suspension_reason');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_at');

            // Two-factor authentication
            $table->boolean('two_factor_enabled')->default(false)->after('last_activity_at');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');

            // Localization
            $table->string('timezone')->default('UTC')->after('two_factor_recovery_codes');
            $table->string('language')->default('en')->after('timezone');

            // Soft deletes
            $table->softDeletes()->after('updated_at');

            // Indexes
            $table->index(['institution_id']);
            $table->index(['status']);
            $table->index(['is_suspended']);
            $table->index(['last_activity_at']);
            $table->index(['created_at']);
        });

        // Add foreign key constraint
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('institution_id')->references('id')->on('tenants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropIndex(['institution_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['is_suspended']);
            $table->dropIndex(['last_activity_at']);
            $table->dropIndex(['created_at']);

            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone',
                'avatar',
                'institution_id',
                'profile_data',
                'preferences',
                'status',
                'is_suspended',
                'suspended_at',
                'suspension_reason',
                'last_login_at',
                'last_activity_at',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'timezone',
                'language',
            ]);
        });
    }
};
