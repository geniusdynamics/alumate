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
        Schema::create('attribution_touches', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('session_id')->nullable();
            $table->enum('event_type', ['page_view', 'click', 'form_submit', 'purchase', 'signup', 'login'])->default('page_view');
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable();
            $table->decimal('value', 10, 2)->default(0);
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['tenant_id', 'timestamp']);
            $table->index(['tenant_id', 'user_id', 'timestamp']);
            $table->index(['tenant_id', 'source', 'timestamp']);
            $table->index(['tenant_id', 'event_type', 'timestamp']);
            $table->index(['session_id']);
            $table->index(['timestamp']);

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribution_touches');
    }
};