<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_onboardings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('current_step')->default(1);
            $table->integer('total_steps')->default(6);
            $table->json('completed_steps')->nullable();
            $table->json('step_data')->nullable(); // Store data for each step
            $table->string('status')->default('in_progress'); // in_progress, completed, abandoned
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('source')->nullable(); // how they found the platform
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        // Add onboarding status to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('onboarding_status')->default('pending')->after('status');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_status');
            $table->json('onboarding_data')->nullable()->after('onboarding_completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_onboardings');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['onboarding_status', 'onboarding_completed_at', 'onboarding_data']);
        });
    }
};
