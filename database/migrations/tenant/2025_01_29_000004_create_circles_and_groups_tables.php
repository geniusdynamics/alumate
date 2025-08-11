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
        // Create circles table
        Schema::create('circles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['school_year', 'multi_school', 'custom']);
            $table->json('criteria');
            $table->integer('member_count')->default(0);
            $table->boolean('auto_generated')->default(false);
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'auto_generated']);
            $table->index('member_count');
            $table->index('created_at');
        });

        // Create groups table
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['school', 'custom', 'interest', 'professional']);
            $table->enum('privacy', ['public', 'private', 'secret']);
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->unsignedBigInteger('creator_id');
            $table->json('settings')->nullable();
            $table->integer('member_count')->default(0);
            $table->timestamps();

            // Foreign key constraints - only if institutions table exists
            if (Schema::hasTable('institutions')) {
                $table->foreign('institution_id')
                    ->references('id')
                    ->on('institutions')
                    ->onDelete('cascade');
            }

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes for performance
            $table->index(['type', 'privacy']);
            $table->index('institution_id');
            $table->index('creator_id');
            $table->index('member_count');
            $table->index('created_at');
        });

        // Create circle_memberships table
        Schema::create('circle_memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('circle_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('joined_at')->useCurrent();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('circle_id')
                ->references('id')
                ->on('circles')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Unique constraint to prevent duplicate memberships
            $table->unique(['circle_id', 'user_id']);

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['circle_id', 'status']);
            $table->index('joined_at');
        });

        // Create group_memberships table
        Schema::create('group_memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['member', 'moderator', 'admin'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->enum('status', ['active', 'pending', 'blocked'])->default('active');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Unique constraint to prevent duplicate memberships
            $table->unique(['group_id', 'user_id']);

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'role']);
            $table->index('joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('circle_memberships');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('circles');
    }
};
