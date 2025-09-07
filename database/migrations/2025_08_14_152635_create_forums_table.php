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
        Schema::create('forums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#3B82F6'); // Hex color for forum theme
            $table->string('icon')->nullable(); // Icon class or emoji
            $table->enum('visibility', ['public', 'private', 'group_only'])->default('public');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Group association (optional - for group-specific forums)
            $table->unsignedBigInteger('group_id')->nullable();

            // Moderation settings
            $table->boolean('requires_approval')->default(false);
            $table->boolean('allow_anonymous')->default(false);

            // Statistics (denormalized for performance)
            $table->integer('topics_count')->default(0);
            $table->integer('posts_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['group_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forums');
    }
};
