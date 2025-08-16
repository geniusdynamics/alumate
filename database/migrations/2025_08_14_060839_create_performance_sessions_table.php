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
        Schema::create('performance_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('referrer', 500)->nullable();
            $table->string('user_agent', 500);
            $table->integer('viewport_width');
            $table->integer('viewport_height');
            $table->integer('screen_width');
            $table->integer('screen_height');
            $table->integer('screen_color_depth')->nullable();
            $table->string('connection_type', 50)->nullable();
            $table->decimal('connection_downlink', 8, 2)->nullable();
            $table->integer('connection_rtt')->nullable();
            $table->boolean('connection_save_data')->default(false);
            $table->bigInteger('memory_used')->nullable();
            $table->bigInteger('memory_total')->nullable();
            $table->bigInteger('memory_limit')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('tenant_id')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_sessions');
    }
};
