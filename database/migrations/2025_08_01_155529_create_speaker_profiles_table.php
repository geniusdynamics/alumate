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
        Schema::create('speaker_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('speaker_title')->nullable(); // Professional title for speaking
            $table->text('bio'); // Speaker biography
            $table->text('speaking_experience')->nullable(); // Previous speaking experience
            $table->json('expertise_topics'); // Array of topics they can speak about
            $table->json('speaking_formats'); // Array: keynote, workshop, panel, webinar, etc.
            $table->json('target_audiences'); // Array: students, professionals, executives, etc.
            $table->json('industries'); // Array of industries they can speak to
            $table->decimal('speaking_fee', 10, 2)->nullable(); // Fee for speaking (null = free)
            $table->boolean('travel_willing')->default(false); // Willing to travel
            $table->integer('max_travel_distance')->nullable(); // Max travel distance in km
            $table->boolean('virtual_speaking')->default(true); // Available for virtual events
            $table->json('availability_preferences')->nullable(); // Preferred days/times
            $table->string('preferred_contact_method')->default('email'); // email, phone, platform
            $table->text('special_requirements')->nullable(); // AV, accommodation, etc.
            $table->json('past_speaking_engagements')->nullable(); // Array of past engagements
            $table->string('demo_video_url')->nullable(); // Link to demo video
            $table->json('testimonials')->nullable(); // Array of testimonials
            $table->decimal('rating', 3, 2)->default(0); // Average rating from past events
            $table->integer('total_engagements')->default(0); // Total speaking engagements
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('last_engagement_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'is_featured']);
            $table->index('speaking_fee');
            $table->index('virtual_speaking');
            $table->index('travel_willing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaker_profiles');
    }
};
