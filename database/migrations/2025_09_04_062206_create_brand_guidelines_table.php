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
        Schema::create('brand_guidelines', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('brand_config_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('usage_rules')->nullable();
            $table->json('color_guidelines')->nullable();
            $table->json('typography_guidelines')->nullable();
            $table->json('logo_guidelines')->nullable();
            $table->json('dos_and_donts')->nullable();
            $table->json('brand_voice_tone')->nullable();
            $table->json('brand_personality')->nullable();
            $table->json('target_audience')->nullable();
            $table->json('brand_values')->nullable();
            $table->json('legal_restrictions')->nullable();
            $table->json('contact_information')->nullable();
            $table->json('review_process')->nullable();
            $table->integer('version')->default(1);
            $table->date('effective_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('tenant_id');
            $table->index('brand_config_id');
            $table->index('slug');
            $table->index('version');
            $table->index('is_active');
            $table->index('requires_approval');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_guidelines');
    }
};