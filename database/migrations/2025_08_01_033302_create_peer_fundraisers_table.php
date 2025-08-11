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
        Schema::create('peer_fundraisers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('fundraising_campaigns')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('personal_message')->nullable();
            $table->decimal('goal_amount', 10, 2)->nullable();
            $table->decimal('raised_amount', 10, 2)->default(0);
            $table->string('status')->default('active'); // active, paused, completed
            $table->json('social_links')->nullable(); // sharing links for social media
            $table->integer('donor_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campaign_id', 'status']);
            $table->index(['user_id']);
            $table->unique(['campaign_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peer_fundraisers');
    }
};
