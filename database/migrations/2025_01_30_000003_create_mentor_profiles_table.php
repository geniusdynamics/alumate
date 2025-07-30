<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio');
            $table->json('expertise_areas'); // Array of expertise areas/skills
            $table->enum('availability', ['high', 'medium', 'low'])->default('medium');
            $table->integer('max_mentees')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'availability']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_profiles');
    }
};