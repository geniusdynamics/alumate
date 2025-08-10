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
        Schema::create('lead_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger_type', ['form_submission', 'page_visit', 'email_open', 'email_click', 'demo_request', 'trial_signup', 'company_size', 'job_title', 'industry']);
            $table->json('conditions'); // Conditions that must be met
            $table->integer('points'); // Points to add/subtract
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Rule execution order
            $table->timestamps();
            
            $table->index(['trigger_type', 'is_active']);
            $table->index(['priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_scoring_rules');
    }
};
