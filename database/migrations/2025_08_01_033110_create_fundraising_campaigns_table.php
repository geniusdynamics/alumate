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
        Schema::create('fundraising_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('story')->nullable();
            $table->decimal('goal_amount', 12, 2);
            $table->decimal('raised_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft'); // draft, active, paused, completed, cancelled
            $table->string('type')->default('general'); // general, scholarship, emergency, project
            $table->json('media_urls')->nullable(); // images, videos
            $table->json('settings')->nullable(); // visibility, sharing options, etc.
            $table->boolean('allow_peer_fundraising')->default(false);
            $table->boolean('show_donor_names')->default(true);
            $table->boolean('allow_anonymous_donations')->default(true);
            $table->text('thank_you_message')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('cascade');
            $table->integer('donor_count')->default(0);
            $table->json('analytics_data')->nullable(); // views, shares, etc.
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'start_date', 'end_date']);
            $table->index(['institution_id', 'status']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundraising_campaigns');
    }
};
