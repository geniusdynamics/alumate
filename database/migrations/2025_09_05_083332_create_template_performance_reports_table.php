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
        Schema::create('template_performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('report_type'); // 'template_performance', 'comparison', 'trend_analysis', etc.
            $table->json('parameters')->nullable(); // Report generation parameters
            $table->json('data')->nullable(); // Cached report data
            $table->string('format')->default('json'); // json, csv, pdf, excel
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'report_type']);
            $table->index(['tenant_id', 'status']);
            $table->index('generated_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_performance_reports');
    }
};
