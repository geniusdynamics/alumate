<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->datetime('checked_in_at');
            $table->string('check_in_method')->default('manual'); // manual, qr_code, nfc, etc.
            $table->json('location_data')->nullable(); // GPS coordinates, venue area, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate check-ins
            $table->unique(['event_id', 'user_id']);
            
            // Indexes
            $table->index(['event_id', 'checked_in_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_check_ins');
    }
};