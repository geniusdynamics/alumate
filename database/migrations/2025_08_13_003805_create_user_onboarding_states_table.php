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
        Schema::create('user_onboarding_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('has_completed_onboarding')->default(false);
            $table->boolean('has_skipped_onboarding')->default(false);
            $table->json('completed_steps')->nullable();
            $table->integer('last_active_step')->default(0);
            $table->boolean('profile_completion_dismissed')->default(false);
            $table->boolean('feature_discovery_viewed')->default(false);
            $table->json('explored_features')->nullable();
            $table->json('whats_new_viewed')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_states');
    }
};
