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
        Schema::create('custom_event_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('event_name');
            $table->json('event_data'); // The actual event data payload
            $table->json('context')->nullable(); // Additional context (user properties, session info, etc.)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('referrer')->nullable();
            $table->string('page_url')->nullable();
            $table->timestamp('occurred_at');
            $table->boolean('is_compliant')->default(true);
            $table->boolean('consent_given')->default(true);
            $table->timestamp('data_retention_until')->nullable();
            $table->string('analytics_version')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['tenant_id', 'event_name']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['tenant_id', 'session_id']);
            $table->index(['event_name', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index('session_id');
            $table->index('occurred_at');
            $table->index('is_compliant');

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_event_tracking');
    }
};