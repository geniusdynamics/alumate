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
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // 'consent_granted', 'consent_updated', 'consent_withdrawn', 'data_deleted'
            $table->json('old_values')->nullable(); // Previous consent state
            $table->json('new_values')->nullable(); // New consent state
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable(); // User who performed the action
            $table->timestamp('timestamp');
            $table->text('reason')->nullable(); // Reason for the action
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'action']);
            $table->index(['tenant_id', 'timestamp']);
            $table->index(['user_id', 'timestamp']);
            $table->index('performed_by');
            $table->index('action');

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};
