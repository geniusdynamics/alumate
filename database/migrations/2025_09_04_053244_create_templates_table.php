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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->enum('category', ['landing', 'homepage', 'form', 'email', 'social']);
            $table->enum('audience_type', ['individual', 'institution', 'employer', 'general']);
            $table->enum('campaign_type', [
                'onboarding', 'event_promotion', 'donation', 'networking',
                'career_services', 'recruiting', 'leadership', 'marketing'
            ]);
            $table->json('structure')->nullable();
            $table->json('default_config')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->string('preview_image')->nullable();
            $table->string('preview_url')->nullable();
            $table->unsignedTinyInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'audience_type']);
            $table->index(['tenant_id', 'campaign_type']);
            $table->index(['tenant_id', 'is_premium']);
            $table->index(['tenant_id', 'usage_count']);
            $table->index('slug');
            $table->unique(['tenant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
