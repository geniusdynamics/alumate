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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->enum('sync_type', ['ga', 'matomo', 'unified', 'discrepancy_detection']);
            $table->enum('status', ['success', 'failed', 'partial']);
            $table->json('discrepancies')->nullable(); // Store discrepancy data as JSON
            $table->timestamp('timestamp');
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'sync_type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'timestamp']);
            $table->index('sync_type');
            $table->index('status');
            $table->index('timestamp');

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};