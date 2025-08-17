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
        Schema::create('campaign_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('fundraising_campaigns')->onDelete('cascade');
            $table->foreignId('donor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('peer_fundraiser_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_anonymous')->default(false);
            $table->string('donor_name')->nullable(); // for anonymous or non-user donations
            $table->string('donor_email')->nullable();
            $table->text('message')->nullable();
            $table->string('payment_method')->nullable(); // stripe, paypal, etc.
            $table->string('payment_id')->nullable(); // external payment reference
            $table->string('status')->default('pending'); // pending, completed, failed, refunded
            $table->json('payment_data')->nullable(); // payment gateway response
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency')->nullable(); // monthly, quarterly, yearly
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campaign_id', 'status']);
            $table->index(['donor_id']);
            $table->index(['peer_fundraiser_id']);
            $table->index(['status', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_donations');
    }
};
