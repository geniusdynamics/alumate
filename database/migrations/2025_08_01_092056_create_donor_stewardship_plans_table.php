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
        Schema::create('donor_stewardship_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_profile_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'paused'])->default('draft');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('goals')->nullable(); // Specific stewardship goals
            $table->json('strategies')->nullable(); // Planned strategies and tactics
            $table->json('milestones')->nullable(); // Key milestones and deadlines
            $table->decimal('target_gift_amount', 15, 2)->nullable();
            $table->string('target_gift_purpose')->nullable();
            $table->date('target_ask_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('priority')->default(3); // 1=high, 2=medium, 3=low
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['donor_profile_id', 'status']);
            $table->index(['assigned_to', 'priority']);
            $table->index('target_ask_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_stewardship_plans');
    }
};
