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
        Schema::create('career_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('company');
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_current')->default(false);
            $table->json('achievements')->nullable();
            $table->string('location')->nullable();
            $table->string('company_logo_url')->nullable();
            $table->string('industry')->nullable();
            $table->string('employment_type')->nullable(); // full-time, part-time, contract, etc.
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'start_date']);
            $table->index(['user_id', 'is_current']);
            $table->index('company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_timelines');
    }
};