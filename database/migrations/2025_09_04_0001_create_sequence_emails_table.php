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
        Schema::create('sequence_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('email_sequences')->onDelete('cascade');
            $table->foreignId('template_id');
            $table->string('subject_line', 255);
            $table->integer('delay_hours')->default(0);
            $table->integer('send_order');
            $table->json('trigger_conditions')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['sequence_id', 'send_order'], 'idx_sequence_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_emails');
    }
};