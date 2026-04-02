<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create insights table for storing AI-generated insights and recommendations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insights', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->enum('type', ['trend', 'recommendation'])->index();
            $table->json('data'); // Contains title, description, impact_score, metrics
            $table->enum('status', ['active', 'dismissed', 'implemented'])->default('active')->index();
            $table->decimal('effectiveness_score', 5, 2)->nullable();
            $table->timestamp('tracked_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'tracked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insights');
    }
};