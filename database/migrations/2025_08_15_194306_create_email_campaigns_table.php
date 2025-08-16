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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject');
            $table->longText('content');
            $table->json('template_data')->nullable();
            $table->enum('type', ['newsletter', 'announcement', 'event', 'fundraising', 'engagement']);
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled']);
            $table->string('provider')->default('internal'); // mailchimp, constant_contact, mautic, internal
            $table->string('provider_campaign_id')->nullable();
            $table->json('provider_data')->nullable();
            $table->json('audience_criteria')->nullable(); // targeting criteria
            $table->json('personalization_rules')->nullable();
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('sent_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('unsubscribed_count')->default(0);
            $table->integer('bounced_count')->default(0);
            $table->decimal('open_rate', 5, 2)->default(0);
            $table->decimal('click_rate', 5, 2)->default(0);
            $table->decimal('unsubscribe_rate', 5, 2)->default(0);
            $table->decimal('bounce_rate', 5, 2)->default(0);
            $table->boolean('is_ab_test')->default(false);
            $table->string('ab_test_variant')->nullable(); // A, B
            $table->foreignId('ab_test_parent_id')->nullable()->constrained('email_campaigns')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->index(['scheduled_at']);
            $table->index(['created_by']);
        });

        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_campaigns')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->enum('status', ['pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'unsubscribed']);
            $table->datetime('sent_at')->nullable();
            $table->datetime('delivered_at')->nullable();
            $table->datetime('opened_at')->nullable();
            $table->datetime('clicked_at')->nullable();
            $table->datetime('bounced_at')->nullable();
            $table->datetime('unsubscribed_at')->nullable();
            $table->json('tracking_data')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['campaign_id', 'status']);
            $table->index(['user_id']);
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // newsletter, announcement, event, etc.
            $table->longText('html_content');
            $table->longText('text_content')->nullable();
            $table->json('variables')->nullable(); // available template variables
            $table->json('design_data')->nullable(); // drag-drop builder data
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'category']);
            $table->index(['is_active']);
        });

        Schema::create('email_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_event'); // user_registered, post_created, event_created, etc.
            $table->json('trigger_conditions')->nullable();
            $table->json('audience_criteria')->nullable();
            $table->foreignId('template_id')->constrained('email_templates')->onDelete('cascade');
            $table->integer('delay_minutes')->default(0); // delay before sending
            $table->boolean('is_active')->default(true);
            $table->integer('sent_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['trigger_event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_automation_rules');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaigns');
    }
};
