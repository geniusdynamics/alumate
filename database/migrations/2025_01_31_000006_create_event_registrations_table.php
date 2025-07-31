<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['registered', 'waitlisted', 'cancelled', 'attended', 'no_show'])->default('registered');
            $table->datetime('registered_at');
            $table->datetime('checked_in_at')->nullable();
            $table->integer('guests_count')->default(0);
            $table->json('guest_details')->nullable(); // Names and details of guests
            $table->text('special_requirements')->nullable();
            $table->json('registration_data')->nullable(); // Additional form data
            $table->decimal('amount_paid', 8, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->datetime('cancelled_at')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate registrations
            $table->unique(['event_id', 'user_id']);
            
            // Indexes
            $table->index(['event_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('registered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};