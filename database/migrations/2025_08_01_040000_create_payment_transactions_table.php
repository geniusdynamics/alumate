<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained('campaign_donations')->onDelete('cascade');
            $table->string('transaction_type')->default('payment'); // payment, refund, chargeback
            $table->string('gateway')->index(); // stripe, paypal, bank_transfer
            $table->string('gateway_transaction_id')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->index(); // pending, completed, failed, cancelled
            $table->json('gateway_response')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('fee_amount', 8, 2)->nullable();
            $table->string('fee_currency', 3)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'gateway_transaction_id']);
            $table->index(['status', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};