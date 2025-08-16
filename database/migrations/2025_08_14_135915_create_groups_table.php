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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('custom'); // school, custom, interest, professional
            $table->string('privacy')->default('public'); // public, private, secret
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->json('settings')->nullable(); // Group-specific settings
            $table->integer('member_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'privacy']);
            $table->index('institution_id');
            $table->index('creator_id');
            $table->index('member_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
