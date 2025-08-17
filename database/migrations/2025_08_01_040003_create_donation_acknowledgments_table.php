<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained('campaign_donations')->onDelete('cascade');
            $table->string('type'); // email, letter, phone, public_recognition
            $table->string('status')->default('pending'); // pending, sent, delivered, failed
            $table->json('recipient_info');
            $table->text('message')->nullable();
            $table->string('template_used')->nullable();
            $table->json('personalization_data')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('delivery_metadata')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['donation_id', 'type']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_acknowledgments');
    }
};
