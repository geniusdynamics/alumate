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
            if (! Schema::hasColumn('institutions', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }
            if (! Schema::hasColumn('institutions', 'domain')) {
                $table->string('domain')->nullable()->unique()->after('website');
            }
            if (! Schema::hasColumn('institutions', 'email')) {
                $table->string('email')->nullable()->after('domain');
            }
            if (! Schema::hasColumn('institutions', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (! Schema::hasColumn('institutions', 'address')) {
                $table->json('address')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('institutions', 'logo_url')) {
                $table->string('logo_url')->nullable()->after('address');
            }
            if (! Schema::hasColumn('institutions', 'banner_url')) {
                $table->string('banner_url')->nullable()->after('logo_url');
            }
            if (! Schema::hasColumn('institutions', 'established_year')) {
                $table->integer('established_year')->nullable()->after('banner_url');
            }
            if (! Schema::hasColumn('institutions', 'student_count')) {
                $table->integer('student_count')->nullable()->after('established_year');
            }
            if (! Schema::hasColumn('institutions', 'alumni_count')) {
                $table->integer('alumni_count')->nullable()->after('student_count');
            }
            if (! Schema::hasColumn('institutions', 'settings')) {
                $table->json('settings')->nullable()->after('alumni_count');
            }
            if (! Schema::hasColumn('institutions', 'subscription_plan')) {
                $table->string('subscription_plan')->default('basic')->after('settings');
            }
            if (! Schema::hasColumn('institutions', 'subscription_status')) {
                $table->string('subscription_status')->default('trial')->after('subscription_plan');
            }
            if (! Schema::hasColumn('institutions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            }
            if (! Schema::hasColumn('institutions', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('trial_ends_at');
            }
            if (! Schema::hasColumn('institutions', 'status')) {
                $table->string('status')->default('active')->after('verified_at');
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
                'slug',
                'domain',
                'email',
                'phone',
                'address',
                'logo_url',
                'banner_url',
                'established_year',
                'student_count',
                'alumni_count',
                'settings',
                'subscription_plan',
                'subscription_status',
                'trial_ends_at',
                'verified_at',
                'status',
            ]);
        });
    }
};
