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
        Schema::create('template_performance_dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('configuration')->nullable(); // Dashboard layout and widget settings
            $table->json('filters')->nullable(); // Default filters for the dashboard
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('cached_metrics')->nullable(); // Cached performance metrics
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('last_updated_at');
            
            // Foreign key constraint for tenant_id
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_performance_dashboards');
    }
};