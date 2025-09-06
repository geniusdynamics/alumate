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
        Schema::create('email_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('email_campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('email_template_id')->nullable()->constrained('templates')->onDelete('set null');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email', 255)->index();
            $table->string('subject_line', 255)->nullable();
            $table->timestamp('send_date')->index();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamp('opened_at')->nullable()->index();
            $table->timestamp('clicked_at')->nullable()->index();
            $table->timestamp('converted_at')->nullable()->index();
            $table->timestamp('unsubscribed_at')->nullable()->index();
            $table->timestamp('bounced_at')->nullable()->index();
            $table->timestamp('complained_at')->nullable()->index();
            $table->enum('delivery_status', [
                'sent',
                'delivered',
                'opened',
                'clicked',
                'converted',
                'bounced',
                'complaint',
                'unsubscribed'
            ])->default('sent')->index();
            $table->integer('open_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->text('bounce_reason')->nullable();
            $table->text('complaint_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'tablet'])->nullable()->index();
            $table->string('browser', 50)->nullable()->index();
            $table->string('location', 255)->nullable();
            $table->text('referrer_url')->nullable();
            $table->decimal('conversion_value', 10, 2)->default(0.00);
            $table->enum('conversion_type', [
                'purchase',
                'signup',
                'download',
                'contact',
                'custom'
            ])->nullable();
            $table->integer('funnel_stage')->default(1);
            $table->string('ab_test_variant', 100)->nullable()->index();
            $table->json('tags')->nullable();
            $table->json('custom_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'send_date']);
            $table->index(['tenant_id', 'delivery_status']);
            $table->index(['tenant_id', 'email_campaign_id']);
            $table->index(['tenant_id', 'recipient_email']);
            $table->index(['tenant_id', 'device_type']);
            $table->index(['tenant_id', 'ab_test_variant']);
            $table->index(['send_date', 'delivery_status']);
            $table->index(['tenant_id', 'send_date', 'delivery_status']);
            $table->index(['tenant_id', 'opened_at']);
            $table->index(['tenant_id', 'clicked_at']);
            $table->index(['tenant_id', 'converted_at']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'email_campaign_id', 'send_date']);
            $table->index(['tenant_id', 'recipient_id', 'send_date']);
            
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_analytics');
    }
};