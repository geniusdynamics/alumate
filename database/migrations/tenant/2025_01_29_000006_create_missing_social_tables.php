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
        // Only create tables that don't exist yet

        // Post engagements for likes, comments, shares, etc.
        if (! Schema::hasTable('post_engagements') && Schema::hasTable('posts')) {
            Schema::create('post_engagements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('type', ['like', 'love', 'celebrate', 'support', 'insightful', 'comment', 'share', 'bookmark']);
                $table->json('metadata')->nullable();
                $table->timestamp('created_at');

                // Prevent duplicate engagements of same type
                $table->unique(['post_id', 'user_id', 'type']);
                $table->index(['post_id', 'type']);
                $table->index('user_id');
            });
        }

        // Circle memberships - skip if already created in circles_and_groups migration
        if (! Schema::hasTable('circle_memberships') && Schema::hasTable('circles')) {
            Schema::create('circle_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('circle_id')->constrained('circles')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('joined_at');
                $table->enum('status', ['active', 'inactive'])->default('active');

                $table->unique(['circle_id', 'user_id']);
                $table->index('user_id');
                $table->index('status');
            });
        }

        // Group memberships with roles - skip if already created in circles_and_groups migration
        if (! Schema::hasTable('group_memberships') && Schema::hasTable('groups')) {
            Schema::create('group_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('role', ['member', 'moderator', 'admin'])->default('member');
                $table->timestamp('joined_at');
                $table->enum('status', ['active', 'pending', 'blocked'])->default('active');

                $table->unique(['group_id', 'user_id']);
                $table->index('user_id');
                $table->index('role');
                $table->index('status');
            });
        }

        // Alumni connections for networking
        if (! Schema::hasTable('alumni_connections')) {
            Schema::create('alumni_connections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requester_id');
                $table->unsignedBigInteger('recipient_id');
                $table->enum('status', ['pending', 'accepted', 'declined', 'blocked'])->default('pending');
                $table->text('message')->nullable();
                $table->timestamp('connected_at')->nullable();
                $table->timestamps();

                $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['requester_id', 'recipient_id']);
                $table->index(['requester_id', 'status']);
                $table->index(['recipient_id', 'status']);
            });
        }

        // Comments for threaded discussions
        if (! Schema::hasTable('comments') && Schema::hasTable('posts')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
                $table->text('content');
                $table->json('mentions')->nullable(); // Array of mentioned user IDs
                $table->timestamps();

                $table->index('post_id');
                $table->index('user_id');
                $table->index('parent_id');
            });
        }

        // Post drafts and scheduled posts
        if (! Schema::hasTable('post_drafts')) {
            Schema::create('post_drafts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->json('media_urls')->nullable();
                $table->enum('post_type', ['text', 'image', 'video', 'career_update', 'achievement', 'event'])->default('text');
                $table->enum('visibility', ['public', 'circles', 'groups', 'private'])->default('circles');
                $table->json('circle_ids')->nullable();
                $table->json('group_ids')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('scheduled_for')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamps();

                $table->index('user_id');
                $table->index('scheduled_for');
                $table->index('is_published');
            });
        }

        // Group invitations
        if (! Schema::hasTable('group_invitations') && Schema::hasTable('groups')) {
            Schema::create('group_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');
                $table->text('message')->nullable();
                $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['group_id', 'user_id']);
                $table->index('user_id');
                $table->index('status');
                $table->index('expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_invitations');
        Schema::dropIfExists('post_drafts');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('alumni_connections');
        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('circle_memberships');
        Schema::dropIfExists('post_engagements');
    }
};
