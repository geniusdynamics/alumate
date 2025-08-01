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
        Schema::create('donor_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Staff member who logged interaction
            $table->enum('type', ['call', 'email', 'meeting', 'event', 'letter', 'visit', 'proposal', 'other']);
            $table->string('subject');
            $table->text('description');
            $table->json('participants')->nullable(); // Other people involved
            $table->enum('outcome', ['positive', 'neutral', 'negative', 'follow_up_needed'])->nullable();
            $table->date('interaction_date');
            $table->time('duration')->nullable();
            $table->json('attachments')->nullable(); // File paths or references
            $table->json('follow_up_actions')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->decimal('potential_gift_amount', 15, 2)->nullable();
            $table->text('private_notes')->nullable(); // Internal notes not shared with donor
            $table->timestamps();
            $table->softDeletes();

            $table->index(['donor_profile_id', 'interaction_date']);
            $table->index(['type', 'outcome']);
            $table->index('next_follow_up_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_interactions');
    }
};
