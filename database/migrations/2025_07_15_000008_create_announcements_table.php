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

        // Messages table is created in the communication migration

        // Discussions table is created in the communication migration

        // Discussion-related tables are created in the communication migration

        // Employer ratings table is created in the communication migration

        // Help tickets tables are created in the communication migration
    }

    public function down()
    {
        // Help tickets, Employer ratings, Discussion-related, Messages tables are dropped in the communication migration
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
