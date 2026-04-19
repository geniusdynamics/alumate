<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('consent_type'); // analytics, marketing, cookies, tos, privacy
            $table->boolean('granted')->default(false);
            $table->text('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'consent_type']);
            $table->index(['consent_type', 'granted']);
        });

        // Create data export requests table
        Schema::create('data_export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('format')->default('json'); // json, csv, xml
            $table->string('file_path')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Create data deletion requests table
        Schema::create('data_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, processing, completed
            $table->text('reason')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('scheduled_deletion_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_deletion_requests');
        Schema::dropIfExists('data_export_requests');
        Schema::dropIfExists('legal_consents');
    }
};
