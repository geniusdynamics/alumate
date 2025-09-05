<?php

declare(strict_types=1);

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
        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->json('preferences')->nullable(); // Granular subscription controls
            $table->json('frequency_settings')->nullable(); // Frequency settings for each category
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('consent_withdrawn_at')->nullable();
            $table->timestamp('double_opt_in_verified_at')->nullable();
            $table->string('double_opt_in_token')->nullable()->unique();
            $table->string('unsubscribe_token')->nullable()->unique();
            $table->boolean('gdpr_compliant')->default(false);
            $table->boolean('can_spam_compliant')->default(false);
            $table->json('audit_trail')->nullable(); // Track all preference changes
            $table->timestamps();

            // Indexes for performance
            $table->index(['email', 'tenant_id']);
            $table->index(['user_id', 'tenant_id']);
            $table->index('double_opt_in_token');
            $table->index('unsubscribe_token');
            $table->index('consent_given_at');
            $table->index('gdpr_compliant');
            $table->index('can_spam_compliant');

            // Unique constraint for email per tenant
            $table->unique(['email', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_preferences');
    }
};