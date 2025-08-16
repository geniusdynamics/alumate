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
        Schema::create('circle_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_id')->constrained('circles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('joined_at')->useCurrent();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
            
            // Unique constraint to prevent duplicate memberships
            $table->unique(['circle_id', 'user_id']);
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['circle_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_memberships');
    }
};
