<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_donation_id')->constrained('campaign_donations')->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained('fundraising_campaigns')->onDelete('cascade');
            $table->foreignId('donor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('frequency'); // monthly, quarterly, yearly
            $table->string('payment_method');
            $table->json('payment_data')->nullable();
            $table->string('status')->default('active'); // active, paused, cancelled, failed
            $table->date('next_payment_date');
            $table->date('last_payment_date')->nullable();
            $table->integer('total_payments')->default(0);
            $table->decimal('total_amount_collected', 10, 2)->default(0);
            $table->integer('failed_attempts')->default(0);
            $table->date('started_at');
            $table->date('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_payment_date']);
            $table->index(['donor_id', 'status']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_donations');
    }
};
