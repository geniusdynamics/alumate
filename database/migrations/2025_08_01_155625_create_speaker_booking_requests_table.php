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
        Schema::create('speaker_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speaker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_title');
            $table->text('event_description');
            $table->date('event_date');
            $table->time('event_start_time');
            $table->time('event_end_time');
            $table->string('event_location')->nullable(); // Physical location or "Virtual"
            $table->enum('event_format', ['keynote', 'workshop', 'panel', 'webinar', 'seminar', 'other']);
            $table->string('topic_requested');
            $table->integer('expected_audience_size')->nullable();
            $table->json('audience_demographics')->nullable(); // Age, profession, etc.
            $table->enum('event_type', ['virtual', 'in_person', 'hybrid']);
            $table->decimal('budget_offered', 10, 2)->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('additional_notes')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled', 'completed'])->default('pending');
            $table->text('speaker_response')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('booking_details')->nullable(); // Final confirmed details
            $table->decimal('final_fee', 10, 2)->nullable();
            $table->json('feedback')->nullable(); // Post-event feedback
            $table->decimal('rating', 3, 2)->nullable(); // Event rating
            $table->timestamps();

            // Indexes
            $table->index(['speaker_id', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index(['event_date', 'status']);
            $table->index('status');
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaker_booking_requests');
    }
};
