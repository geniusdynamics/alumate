<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('verification_method')->default('manual'); // manual, email_domain, bulk_import, auto
            $table->integer('graduation_year')->nullable();
            $table->string('student_id')->nullable();
            $table->string('degree')->nullable();
            $table->string('major')->nullable();
            $table->string('email_domain')->nullable();
            $table->json('supporting_documents')->nullable(); // Array of document paths
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['institution_id', 'status']);
            $table->index('graduation_year');
        });

        // Add verification_status to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_status')->default('unverified')->after('email_verified_at');
            $table->string('verification_token', 64)->nullable()->unique()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_verifications');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'verification_token', 'verified_at']);
        });
    }
};
