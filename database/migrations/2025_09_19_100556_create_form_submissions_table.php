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
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('form_builders')->onDelete('cascade');
            $table->json('submission_data');
            $table->string('user_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->enum('crm_sync_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->string('crm_lead_id')->nullable();
            $table->json('crm_sync_error')->nullable();
            $table->json('validation_errors')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed', 'synced'])->default('pending');
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['form_id', 'created_at']);
            $table->index(['crm_sync_status']);
            $table->index(['status']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
