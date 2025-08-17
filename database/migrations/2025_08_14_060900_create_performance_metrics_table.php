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
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('performance_sessions')->onDelete('cascade');
            $table->string('name', 100);
            $table->decimal('value', 12, 4);
            $table->string('url', 500);
            $table->json('metadata')->nullable();
            $table->bigInteger('timestamp');
            $table->timestamps();

            // Indexes for performance
            $table->index(['session_id', 'name']);
            $table->index(['name', 'created_at']);
            $table->index(['url', 'name']);
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
