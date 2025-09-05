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
        Schema::create('behavior_events', function (Blueprint $table) {
            $table->id();

            // Tenant and User relationships
            $table->string('tenant_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Event details
            $table->string('event_type');
            $table->json('event_data')->nullable();
            $table->timestamp('timestamp');
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for performance
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'timestamp']);
            $table->index(['user_id', 'event_type']);
            $table->index(['user_id', 'timestamp']);
            $table->index(['event_type', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behavior_events');
    }
};
