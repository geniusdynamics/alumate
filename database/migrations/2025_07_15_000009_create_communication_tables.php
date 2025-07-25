<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('content');
            $table->enum('type', ['direct', 'application_related', 'system'])->default('direct');
            $table->foreignId('related_job_id')->nullable()->constrained('jobs')->onDelete('set null');
            $table->foreignId('related_application_id')->nullable()->constrained('job_applications')->onDelete('set null');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['recipient_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        // Discussions table
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('category', ['general', 'job_search', 'career_advice', 'networking', 'technical'])->default('general');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'created_at']);
            $table->index(['is_pinned', 'last_activity_at']);
            $table->index(['author_id', 'created_at']);
        });

        // Discussion replies table
        Schema::create('discussion_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained('discussions')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->integer('likes_count')->default(0);
            $table->timestamps();

            $table->index(['discussion_id', 'created_at']);
            $table->index(['author_id', 'created_at']);
        });

        // Discussion likes table
        Schema::create('discussion_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('discussion_id')->nullable()->constrained('discussions')->onDelete('cascade');
            $table->foreignId('reply_id')->nullable()->constrained('discussion_replies')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'discussion_id']);
            $table->unique(['user_id', 'reply_id']);
        });

        // Employer ratings table
        Schema::create('employer_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graduate_id')->constrained('graduates')->onDelete('cascade');
            $table->foreignId('employer_id')->constrained('employers')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onDelete('set null');
            $table->integer('overall_rating')->unsigned()->min(1)->max(5);
            $table->integer('communication_rating')->unsigned()->min(1)->max(5);
            $table->integer('work_environment_rating')->unsigned()->min(1)->max(5);
            $table->integer('compensation_rating')->unsigned()->min(1)->max(5);
            $table->text('review')->nullable();
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->boolean('would_recommend')->default(true);
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->unique(['graduate_id', 'employer_id', 'job_id']);
            $table->index(['employer_id', 'is_approved']);
            $table->index(['overall_rating', 'created_at']);
        });

        // Help tickets table
        Schema::create('help_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('subject');
            $table->text('description');
            $table->enum('category', ['technical', 'account', 'job_posting', 'application', 'billing', 'other'])->default('other');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->json('attachments')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['priority', 'status']);
            $table->index(['category', 'created_at']);
        });

        // Help ticket responses table
        Schema::create('help_ticket_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('help_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_internal')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Announcement reads table (tracking who read what)
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('help_ticket_responses');
        Schema::dropIfExists('help_tickets');
        Schema::dropIfExists('employer_ratings');
        Schema::dropIfExists('discussion_likes');
        Schema::dropIfExists('discussion_replies');
        Schema::dropIfExists('discussions');
        Schema::dropIfExists('messages');
    }
};