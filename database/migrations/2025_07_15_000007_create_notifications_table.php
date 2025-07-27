<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create the notifications table first (Laravel's default structure)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type');
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_type']);
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type'); // email, sms, in_app, push
            $table->string('subject')->nullable();
            $table->text('content');
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->string('channel'); // email, sms, in_app, push
            $table->string('status'); // sent, failed, pending
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};