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
        if (!Schema::hasTable('template_analytics_events')) {
            Schema::create('template_analytics_events', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->unsignedBigInteger('template_id');
                $table->unsignedBigInteger('landing_page_id')->nullable();
                $table->enum('event_type', [
                    'page_view',
                    'click',
                    'form_submit',
                    'conversion',
                    'scroll',
                    'time_on_page',
                    'exit',
                    'engagement',
                    'cta_click',
                    'social_share',
                    'download',
                    'video_play',
                    'video_complete',
                ]);
                $table->json('event_data')->nullable();
                $table->string('user_identifier')->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('referrer_url')->nullable();
                $table->string('session_id')->nullable()->index();
                $table->decimal('conversion_value', 10, 2)->nullable()->default(0);
                $table->json('geo_location')->nullable();
                $table->json('device_info')->nullable();
                $table->timestamp('timestamp');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                // Foreign keys
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
                $table->foreign('landing_page_id')->references('id')->on('landing_pages')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

                // Indexes for performance and analytics queries
                $table->index(['tenant_id', 'event_type']);
                $table->index(['tenant_id', 'template_id']);
                $table->index(['tenant_id', 'landing_page_id']);
                $table->index(['tenant_id', 'timestamp']);
                $table->index(['tenant_id', 'event_type', 'timestamp']);
                $table->index(['tenant_id', 'template_id', 'timestamp']);
                $table->index(['tenant_id', 'user_identifier', 'timestamp']);
                $table->index(['session_id', 'timestamp']);
                $table->index('user_agent');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_analytics_events');
    }
};