<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunion_photo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reunion_photo_id')->constrained('reunion_photos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['reunion_photo_id', 'is_approved', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunion_photo_comments');
    }
};