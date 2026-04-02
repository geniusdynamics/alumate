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
        Schema::create('session_recordings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index(); // For tenant isolation
            $table->string('session_id', 100)->unique(); // Unique per tenant
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->jsonb('recording_data'); // JSONB column for PostgreSQL with full-text search
            $table->integer('duration_seconds')->default(0);
            $table->integer('page_views')->default(0);
            $table->integer('interactions_count')->default(0);
            $table->boolean('privacy_masked')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['tenant_id', 'session_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['privacy_masked']);

            // JSONB full-text search index for event types
            $table->index(['recording_data'], 'session_recordings_recording_data_gin', 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_recordings');
    }
};
