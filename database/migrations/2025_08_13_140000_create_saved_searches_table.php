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
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('query');
            $table->json('filters')->nullable();
            $table->boolean('email_alerts')->default(false);
            $table->enum('alert_frequency', ['immediate', 'daily', 'weekly'])->default('daily');
            $table->timestamp('last_run_at')->nullable();
            $table->integer('last_result_count')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['email_alerts', 'alert_frequency']);
            $table->index('last_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};