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
        Schema::table('tenants', function (Blueprint $table) {
            // Add missing columns that the Tenant model expects
            if (!Schema::hasColumn('tenants', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'domain')) {
                $table->string('domain')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('tenants', 'database_name')) {
                $table->string('database_name')->nullable()->after('domain');
            }
            if (!Schema::hasColumn('tenants', 'schema_name')) {
                $table->string('schema_name')->nullable()->after('database_name');
            }
            if (!Schema::hasColumn('tenants', 'status')) {
                $table->string('status')->default('active')->after('schema_name');
            }
            if (!Schema::hasColumn('tenants', 'settings')) {
                $table->json('settings')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tenants', 'subscription_plan')) {
                $table->string('subscription_plan')->nullable()->after('settings');
            }
            if (!Schema::hasColumn('tenants', 'subscription_status')) {
                $table->string('subscription_status')->default('trial')->after('subscription_plan');
            }
            if (!Schema::hasColumn('tenants', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            }
            if (!Schema::hasColumn('tenants', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('trial_ends_at');
            }
            if (!Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('tenants', 'contact_information')) {
                $table->text('contact_information')->nullable()->after('address');
            }
            if (!Schema::hasColumn('tenants', 'plan')) {
                $table->string('plan')->nullable()->after('contact_information');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $columnsToCheck = [
                'name', 'slug', 'domain', 'database_name', 'schema_name', 
                'status', 'settings', 'subscription_plan', 'subscription_status', 
                'trial_ends_at', 'created_by', 'address', 'contact_information', 'plan'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};