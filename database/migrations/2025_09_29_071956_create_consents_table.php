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
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('category'); // 'analytics', 'marketing', 'tracking', 'profiling'
            $table->boolean('granted')->default(true);
            $table->timestamp('timestamp');
            $table->timestamp('withdrawal_date')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('consent_version')->default('1.0');
            $table->text('withdrawal_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'granted']);
            $table->index(['tenant_id', 'timestamp']);
            $table->index(['user_id', 'category']);
            $table->index('withdrawal_date');

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
