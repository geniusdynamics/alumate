<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunion_memory_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reunion_memory_id')->constrained('reunion_memories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['reunion_memory_id', 'is_approved', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunion_memory_comments');
    }
};
