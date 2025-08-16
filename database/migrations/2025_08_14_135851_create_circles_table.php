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
        Schema::create('circles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('custom'); // school_year, multi_school, custom
            $table->json('criteria')->nullable(); // Criteria for auto-generated circles
            $table->integer('member_count')->default(0);
            $table->boolean('auto_generated')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'auto_generated']);
            $table->index('member_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};
