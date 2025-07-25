<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('general'); // general, urgent, maintenance, feature
            $table->string('scope')->default('all'); // all, institution, role
            $table->json('target_audience')->nullable(); // specific institutions, roles, or users
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->boolean('is_published')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['is_published', 'published_at']);
            $table->index(['scope', 'type']);
            $table->index(['expires_at']);
        });

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();
            
            $table->unique(['announcement_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('content');
            $table->string('type')->default('direct'); // direct, application_related, system
            $table->foreignId('related_job_id')->nullable()->constrained('jobs')->onDelete('set null');
            $table->foreignId('related_application_id')->nullable()->constrained('job_applications')->onDelete('set null');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            
            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_id', 'read_at']);
            $table->index(['type', 'created_at']);
        });

        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('category')->default('general'); // general, career, academic, technical
            $table->json('tags')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('views_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            
            $table->index(['category', 'created_at']);
            $table->index(['course_id', 'last_activity_at']);
            $table->index(['is_pinned', 'last_activity_at']);
        });

        Schema::create('discussion_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->foreignId('parent_id')->nullable()->constrained('discussion_replies')->onDelete('cascade');
            $table->integer('likes_count')->default(0);
            $table->boolean('is_solution')->default(false);
            $table->timestamps();
            
            $table->index(['discussion_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('discussion_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_reply_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['discussion_reply_id', 'user_id']);
        });

        Schema::create('employer_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->foreignId('graduate_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('rating'); // 1-5 stars
            $table->text('review')->nullable();
            $table->json('rating_categories')->nullable(); // work_environment, management, benefits, etc.
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['employer_id', 'graduate_id', 'job_id']);
            $table->index(['employer_id', 'is_approved']);
        });

        Schema::create('help_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // technical, academic, career, account, other
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->string('subject');
            $table->text('description');
            $table->json('attachments')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['category', 'priority']);
        });

        Schema::create('help_ticket_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('response');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false); // internal staff notes
            $table->timestamps();
            
            $table->index(['help_ticket_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('help_ticket_responses');
        Schema::dropIfExists('help_tickets');
        Schema::dropIfExists('employer_ratings');
        Schema::dropIfExists('discussion_likes');
        Schema::dropIfExists('discussion_replies');
        Schema::dropIfExists('discussions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};