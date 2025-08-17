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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->enum('lead_type', ['individual', 'institutional', 'enterprise'])->default('individual');
            $table->enum('source', ['homepage', 'demo_request', 'trial_signup', 'contact_form', 'referral', 'organic', 'paid_ads'])->default('homepage');
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'])->default('new');
            $table->integer('score')->default(0);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('utm_data')->nullable(); // UTM parameters and tracking data
            $table->json('form_data')->nullable(); // Additional form fields submitted
            $table->json('behavioral_data')->nullable(); // Page views, time on site, etc.
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->string('crm_id')->nullable(); // External CRM system ID
            $table->timestamp('synced_at')->nullable(); // Last sync with CRM
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index(['lead_type', 'source']);
            $table->index(['assigned_to', 'status']);
            $table->index(['score']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
