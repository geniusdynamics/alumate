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
        Schema::create('email_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('sequence_enrollments');
            $table->foreignId('sequence_email_id')->constrained('sequence_emails');
            $table->foreignId('lead_id')->constrained('leads');
            $table->string('subject');
            $table->enum('status', ['queued', 'sent', 'delivered', 'bounced', 'failed']);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['lead_id', 'status'], 'idx_lead_status');
            $table->index(['sent_at', 'opened_at', 'clicked_at'], 'idx_performance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_sends');
    }
};