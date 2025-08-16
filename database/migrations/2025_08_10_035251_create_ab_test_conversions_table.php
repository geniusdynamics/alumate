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
        Schema::create('ab_test_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('test_id', 100)->index();
            $table->string('variant_id', 100)->index();
            $table->string('goal_id', 100)->index();
            $table->decimal('value', 10, 2);
            $table->string('user_id', 100)->nullable()->index();
            $table->string('session_id', 100)->index();
            $table->enum('audience', ['individual', 'institutional'])->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('converted_at')->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['test_id', 'variant_id', 'goal_id']);
            $table->index(['test_id', 'converted_at']);
            $table->index(['user_id', 'test_id']);
            $table->index(['session_id', 'test_id']);
            $table->index(['audience', 'converted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ab_test_conversions');
    }
};
