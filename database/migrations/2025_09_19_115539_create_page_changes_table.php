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
        Schema::create('page_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('landing_pages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('operation_type'); // 'add', 'update', 'delete', 'move'
            $table->string('component_id')->nullable();
            $table->json('operation_data');
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            $table->integer('sequence_number');
            $table->string('session_id');
            $table->boolean('is_applied')->default(false);
            $table->timestamps();
            
            $table->index(['page_id', 'sequence_number']);
            $table->index(['session_id', 'sequence_number']);
            $table->index(['page_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_changes');
    }
};
