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
        Schema::create('ab_test_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('test_id', 100)->index();
            $table->string('variant_id', 100)->index();
            $table->string('user_id', 100)->nullable()->index();
            $table->string('session_id', 100)->index();
            $table->enum('audience', ['individual', 'institutional'])->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('assigned_at')->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['test_id', 'variant_id']);
            $table->index(['user_id', 'test_id']);
            $table->index(['session_id', 'test_id']);
            $table->index(['audience', 'assigned_at']);

            // Unique constraint to prevent duplicate assignments
            $table->unique(['test_id', 'user_id'], 'unique_user_test_assignment');
            $table->unique(['test_id', 'session_id'], 'unique_session_test_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ab_test_assignments');
    }
};
