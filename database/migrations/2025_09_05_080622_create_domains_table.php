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
        // Check if the domains table exists and has the old structure
        if (Schema::hasTable('domains')) {
            Schema::table('domains', function (Blueprint $table) {
                // Rename the old domain column to domain_name if it exists
                if (Schema::hasColumn('domains', 'domain') && !Schema::hasColumn('domains', 'domain_name')) {
                    $table->renameColumn('domain', 'domain_name');
                }
                
                // Add new columns if they don't exist
                if (!Schema::hasColumn('domains', 'domain_type')) {
                    $table->string('domain_type')->default('custom')->after('domain_name'); // custom, subdomain, tenant
                }
                if (!Schema::hasColumn('domains', 'status')) {
                    $table->string('status')->default('pending')->after('domain_type'); // pending, verifying, verified, failed
                }
                if (!Schema::hasColumn('domains', 'is_primary')) {
                    $table->boolean('is_primary')->default(false)->after('status');
                }
                if (!Schema::hasColumn('domains', 'ssl_enabled')) {
                    $table->boolean('ssl_enabled')->default(false)->after('is_primary');
                }
                if (!Schema::hasColumn('domains', 'ssl_certificate_id')) {
                    $table->string('ssl_certificate_id')->nullable()->after('ssl_enabled');
                }
                if (!Schema::hasColumn('domains', 'dns_records')) {
                    $table->json('dns_records')->nullable()->after('ssl_certificate_id');
                }
                if (!Schema::hasColumn('domains', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('dns_records');
                }
                if (!Schema::hasColumn('domains', 'ssl_expires_at')) {
                    $table->timestamp('ssl_expires_at')->nullable()->after('verified_at');
                }
                if (!Schema::hasColumn('domains', 'verification_error')) {
                    $table->text('verification_error')->nullable()->after('ssl_expires_at');
                }
                if (!Schema::hasColumn('domains', 'domain_config')) {
                    $table->json('domain_config')->nullable()->after('verification_error');
                }

                // Add indexes for performance
                $table->index(['tenant_id', 'status']);
                $table->index(['domain_name']);
                $table->index(['status', 'verified_at']);
                $table->index(['is_primary']);
            });
        } else {
            // Create the domains table if it doesn't exist
            Schema::create('domains', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->string('domain_name')->unique();
                $table->string('domain_type')->default('custom'); // custom, subdomain, tenant
                $table->string('status')->default('pending'); // pending, verifying, verified, failed
                $table->boolean('is_primary')->default(false);
                $table->boolean('ssl_enabled')->default(false);
                $table->string('ssl_certificate_id')->nullable();
                $table->json('dns_records')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('ssl_expires_at')->nullable();
                $table->text('verification_error')->nullable();
                $table->json('domain_config')->nullable();
                $table->timestamps();

                // Foreign key constraint for tenant_id
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

                // Indexes for performance
                $table->index(['tenant_id', 'status']);
                $table->index(['domain_name']);
                $table->index(['status', 'verified_at']);
                $table->index(['is_primary']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            // Drop indexes
            if (Schema::hasTable('domains')) {
                $table->dropIndex(['tenant_id', 'status']);
                $table->dropIndex(['domain_name']);
                $table->dropIndex(['status', 'verified_at']);
                $table->dropIndex(['is_primary']);
                
                // Drop added columns
                $table->dropColumn([
                    'domain_type',
                    'status',
                    'is_primary',
                    'ssl_enabled',
                    'ssl_certificate_id',
                    'dns_records',
                    'verified_at',
                    'ssl_expires_at',
                    'verification_error',
                    'domain_config'
                ]);
                
                // Rename domain_name back to domain
                if (Schema::hasColumn('domains', 'domain_name') && !Schema::hasColumn('domains', 'domain')) {
                    $table->renameColumn('domain_name', 'domain');
                }
            }
        });
    }
};