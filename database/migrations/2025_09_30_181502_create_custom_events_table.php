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
        Schema::create('custom_events', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('definition_id');
            $table->unsignedBigInteger('user_id');
            $table->json('data_json');
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['tenant_id', 'definition_id', 'user_id', 'timestamp']);
            $table->index('tenant_id');
            $table->index('definition_id');
            $table->index('user_id');
            $table->index('timestamp');

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('definition_id')->references('id')->on('custom_event_definitions');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_events');
    }
};
