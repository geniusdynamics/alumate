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
        Schema::create('federation_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('local_type'); // 'post', 'message', 'user', 'group', 'circle'
            $table->unsignedBigInteger('local_id'); // ID of the local entity
            $table->string('protocol'); // 'matrix' or 'activitypub'
            $table->string('federation_id'); // Matrix event ID or ActivityPub object ID
            $table->json('federation_data')->nullable(); // Protocol-specific data
            $table->string('server_name')->nullable(); // Matrix server or ActivityPub instance
            $table->timestamp('federated_at')->nullable();
            $table->timestamps();

            // Indexes for efficient lookups
            $table->index(['local_type', 'local_id']);
            $table->index(['protocol', 'federation_id']);
            $table->unique(['local_type', 'local_id', 'protocol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federation_mappings');
    }
};
