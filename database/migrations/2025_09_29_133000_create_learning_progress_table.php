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
        Schema::create('learning_progress', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->integer('modules_completed')->default(0);
            $table->decimal('total_score', 5, 2)->default(0);
            $table->decimal('engagement_score', 5, 2)->default(0);
            $table->boolean('certified')->default(false);
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'course_id']);
            $table->index(['user_id', 'course_id']);
            $table->unique(['tenant_id', 'user_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_progress');
    }
};