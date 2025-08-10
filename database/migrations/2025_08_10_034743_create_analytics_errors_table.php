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
        Schema::create('analytics_errors', function (Blueprint $table) {
            $table->id();
            $table->string('error_type', 100)->index();
            $table->json('error_data');
            $table->string('session_id', 100)->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('timestamp')->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['error_type', 'timestamp']);
            $table->index(['session_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_errors');
    }
};
