<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->json('media_urls')->nullable(); // Images, videos for the event
            $table->enum('type', ['networking', 'reunion', 'webinar', 'workshop', 'social', 'professional', 'fundraising', 'other']);
            $table->enum('format', ['in_person', 'virtual', 'hybrid']);
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->string('timezone', 50)->default('UTC');

            // Location details
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('virtual_link')->nullable();
            $table->text('virtual_instructions')->nullable();

            // Capacity and registration
            $table->integer('max_capacity')->nullable();
            $table->integer('current_attendees')->default(0);
            $table->boolean('requires_approval')->default(false);
            $table->decimal('ticket_price', 8, 2)->nullable();
            $table->enum('registration_status', ['open', 'closed', 'waitlist'])->default('open');
            $table->datetime('registration_deadline')->nullable();

            // Organizer and visibility
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('cascade');
            $table->enum('visibility', ['public', 'alumni_only', 'institution_only', 'private'])->default('alumni_only');
            $table->json('target_circles')->nullable(); // Circle IDs this event targets
            $table->json('target_groups')->nullable(); // Group IDs this event targets

            // Event settings
            $table->json('settings')->nullable(); // Additional event configuration
            $table->boolean('allow_guests')->default(false);
            $table->integer('max_guests_per_attendee')->default(0);
            $table->boolean('enable_networking')->default(true);
            $table->boolean('enable_checkin')->default(true);

            // Status and metadata
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->json('tags')->nullable(); // Event tags for categorization
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['start_date', 'status']);
            $table->index(['type', 'format']);
            $table->index(['organizer_id', 'status']);
            $table->index(['institution_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
