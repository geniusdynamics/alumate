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
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->json('payload'); // The webhook payload that was sent
            $table->enum('status', ['pending', 'delivered', 'failed', 'skipped'])->default('pending');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->float('response_time')->nullable(); // Response time in milliseconds
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'status']);
            $table->index(['webhook_id', 'event_type']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
