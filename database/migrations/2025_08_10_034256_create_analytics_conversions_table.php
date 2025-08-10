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
        Schema::create('analytics_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('goalId', 100)->index();
            $table->string('goalName', 200);
            $table->string('goalType', 100)->index();
            $table->decimal('value', 10, 2);
            $table->string('trackingCode', 100)->index();
            $table->enum('audience', ['individual', 'institutional'])->index();
            $table->string('session_id', 100)->index();
            $table->string('userId', 100)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('timestamp')->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['audience', 'goalId', 'timestamp']);
            $table->index(['audience', 'goalType', 'timestamp']);
            $table->index(['userId', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_conversions');
    }
};
