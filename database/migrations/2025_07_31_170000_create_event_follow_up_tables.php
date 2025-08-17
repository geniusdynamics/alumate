<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Event feedback and ratings
        Schema::create('event_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('overall_rating')->unsigned()->comment('1-5 rating');
            $table->integer('content_rating')->unsigned()->nullable()->comment('1-5 rating');
            $table->integer('organization_rating')->unsigned()->nullable()->comment('1-5 rating');
            $table->integer('networking_rating')->unsigned()->nullable()->comment('1-5 rating');
            $table->integer('venue_rating')->unsigned()->nullable()->comment('1-5 rating');
            $table->text('feedback_text')->nullable();
            $table->json('feedback_categories')->nullable()->comment('Structured feedback by category');
            $table->boolean('would_recommend')->default(false);
            $table->boolean('would_attend_again')->default(false);
            $table->json('improvement_suggestions')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'overall_rating']);
        });

        // Event highlights and content sharing
        Schema::create('event_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('type')->comment('photo, video, quote, moment, achievement');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('media_urls')->nullable();
            $table->json('metadata')->nullable()->comment('Additional data like location, timestamp, etc.');
            $table->integer('likes_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->timestamp('featured_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'type']);
            $table->index(['event_id', 'is_featured']);
            $table->index(['event_id', 'created_at']);
        });

        // Event highlight interactions
        Schema::create('event_highlight_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('highlight_id')->constrained('event_highlights')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->comment('like, share, comment');
            $table->text('content')->nullable()->comment('For comments');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['highlight_id', 'user_id', 'type']);
            $table->index(['highlight_id', 'type']);
        });

        // Post-event networking connections
        Schema::create('event_networking_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('connected_user_id')->constrained('users')->onDelete('cascade');
            $table->string('connection_type')->comment('met_at_event, mutual_interest, follow_up, collaboration');
            $table->text('connection_note')->nullable();
            $table->json('shared_interests')->nullable();
            $table->boolean('follow_up_requested')->default(false);
            $table->timestamp('connected_at');
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id', 'connected_user_id']);
            $table->index(['event_id', 'connection_type']);
            $table->index(['user_id', 'connected_at']);
        });

        // Event connection recommendations
        Schema::create('event_connection_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recommended_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('match_score', 5, 2)->comment('0-100 compatibility score');
            $table->json('match_reasons')->comment('Why they are recommended');
            $table->json('shared_attributes')->nullable()->comment('Common interests, background, etc.');
            $table->string('status')->default('pending')->comment('pending, viewed, connected, dismissed');
            $table->timestamp('recommended_at');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('acted_on_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id', 'recommended_user_id']);
            $table->index(['event_id', 'user_id', 'status']);
            $table->index(['event_id', 'match_score']);
        });

        // Event follow-up activities
        Schema::create('event_follow_up_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type')->comment('survey_completed, connections_made, content_shared, feedback_given');
            $table->json('activity_data')->nullable();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['event_id', 'activity_type']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_follow_up_activities');
        Schema::dropIfExists('event_connection_recommendations');
        Schema::dropIfExists('event_networking_connections');
        Schema::dropIfExists('event_highlight_interactions');
        Schema::dropIfExists('event_highlights');
        Schema::dropIfExists('event_feedback');
    }
};
