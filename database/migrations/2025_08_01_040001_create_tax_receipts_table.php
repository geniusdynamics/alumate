<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('donor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('donor_name');
            $table->string('donor_email');
            $table->json('donor_address')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->year('tax_year');
            $table->date('receipt_date');
            $table->json('donations'); // Array of donation IDs and amounts
            $table->string('status')->default('generated'); // generated, sent, downloaded
            $table->string('pdf_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['donor_id', 'tax_year']);
            $table->index(['receipt_number']);
            $table->index(['status', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_receipts');
    }
};
