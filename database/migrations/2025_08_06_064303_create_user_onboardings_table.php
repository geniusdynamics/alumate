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
        Schema::create('user_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_new_user')->default(true);
            $table->boolean('has_completed_onboarding')->default(false);
            $table->json('progress')->nullable(); // current_step, completed_steps, skipped_steps
            $table->json('preferences')->nullable(); // user onboarding preferences
            $table->json('explored_features')->nullable(); // array of explored feature IDs
            $table->json('dismissed_prompts')->nullable(); // array of dismissed prompt types
            $table->timestamp('feature_discovery_viewed_at')->nullable();
            $table->timestamp('whats_new_viewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['user_id', 'has_completed_onboarding']);
            $table->index(['user_id', 'is_new_user']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_onboardings');
    }
};
