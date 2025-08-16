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
        Schema::create('landing_page_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('lead_id')->nullable(); // Link to CRM lead
            $table->string('form_name')->nullable();
            $table->json('form_data'); // Submitted form data
            $table->json('utm_data')->nullable(); // UTM tracking data
            $table->json('session_data')->nullable(); // Session and tracking data
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->enum('status', ['new', 'processed', 'converted', 'spam'])->default('new');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['landing_page_id', 'status']);
            $table->index(['lead_id', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_submissions');
    }
};
