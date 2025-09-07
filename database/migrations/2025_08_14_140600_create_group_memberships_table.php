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
        Schema::create('group_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->default('member'); // member, moderator, admin
            $table->timestamp('joined_at')->useCurrent();
            $table->string('status')->default('active'); // active, pending, inactive
            $table->timestamps();

            // Unique constraint to prevent duplicate memberships
            $table->unique(['group_id', 'user_id']);

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_memberships');
    }
};
