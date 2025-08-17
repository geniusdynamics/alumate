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
        Schema::create('scholarship_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained('scholarship_applications')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->decimal('awarded_amount', 10, 2);
            $table->date('award_date');
            $table->enum('status', ['awarded', 'active', 'completed', 'revoked']);
            $table->text('success_story')->nullable();
            $table->json('academic_progress')->nullable();
            $table->json('impact_metrics')->nullable();
            $table->text('thank_you_message')->nullable();
            $table->json('updates')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scholarship_id', 'status']);
            $table->index(['recipient_id', 'status']);
            $table->index(['award_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_recipients');
    }
};
