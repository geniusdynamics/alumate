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
        Schema::create('major_gift_prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_profile_id')->constrained()->onDelete('cascade');
            $table->string('prospect_name');
            $table->text('description');
            $table->decimal('ask_amount', 15, 2);
            $table->string('purpose'); // What the gift will fund
            $table->enum('stage', ['identification', 'qualification', 'cultivation', 'solicitation', 'stewardship', 'closed_won', 'closed_lost']);
            $table->decimal('probability', 3, 2)->default(0.25); // 0.00 to 1.00
            $table->date('expected_close_date')->nullable();
            $table->date('last_activity_date')->nullable();
            $table->foreignId('assigned_officer_id')->constrained('users')->onDelete('cascade');
            $table->json('stakeholders')->nullable(); // Other people involved in the ask
            $table->json('proposal_details')->nullable();
            $table->text('next_steps')->nullable();
            $table->json('barriers')->nullable(); // Potential obstacles
            $table->json('motivations')->nullable(); // Donor motivations
            $table->decimal('actual_amount', 15, 2)->nullable(); // If closed won
            $table->date('close_date')->nullable();
            $table->text('close_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage', 'expected_close_date']);
            $table->index(['assigned_officer_id', 'stage']);
            $table->index(['probability', 'ask_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('major_gift_prospects');
    }
};
