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
        Schema::create('site_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('published_site_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('page_views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('bounce_rate')->default(0); // percentage
            $table->integer('avg_session_duration')->default(0); // seconds
            $table->json('top_pages')->nullable();
            $table->json('traffic_sources')->nullable();
            $table->json('device_breakdown')->nullable();
            $table->json('geographic_data')->nullable();
            $table->json('conversion_funnel')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['published_site_id', 'date']);
            $table->index(['tenant_id', 'date']);
            $table->index(['date']);
            $table->unique(['published_site_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_analytics');
    }
};
