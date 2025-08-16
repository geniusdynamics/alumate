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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('url', 2048);
            $table->json('events'); // Array of event types to listen for
            $table->string('secret')->nullable();
            $table->enum('status', ['active', 'paused', 'disabled'])->default('active');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->json('headers')->nullable(); // Custom headers to send
            $table->integer('timeout')->default(30); // Request timeout in seconds
            $table->integer('retry_attempts')->default(3);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
