<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('learning_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['Course', 'Article', 'Video', 'Book', 'Workshop', 'Certification']);
            $table->string('url');
            $table->json('skill_ids');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->timestamps();
            
            $table->index(['type', 'rating']);
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_resources');
    }
};