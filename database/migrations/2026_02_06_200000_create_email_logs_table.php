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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('sender_email');
            $table->string('subject');
            $table->string('template')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'bounced', 'failed'])->default('queued');
            $table->string('provider')->default('internal');
            $table->string('provider_id')->nullable();
            $table->string('bounce_type')->nullable();
            $table->text('bounce_reason')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('tracking_id')->unique()->nullable();
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes for common queries
            $table->index('status', 'idx_email_logs_status');
            $table->index('provider', 'idx_email_logs_provider');
            $table->index('recipient_email', 'idx_email_logs_recipient');
            $table->index('tracking_id', 'idx_email_logs_tracking');
            $table->index(['status', 'retry_count', 'next_retry_at'], 'idx_email_logs_retry');
            $table->index(['tenant_id', 'created_at'], 'idx_email_logs_tenant');
            $table->index(['sent_at', 'opened_at', 'clicked_at'], 'idx_email_logs_engagement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
