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
        Schema::create('brand_logos', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->string('type')->default('primary'); // primary, secondary, favicon, etc.
            $table->string('url');
            $table->string('alt')->nullable();
            $table->integer('size')->nullable(); // in bytes
            $table->string('mime_type')->nullable();
            $table->boolean('optimized')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->json('variants')->nullable(); // array of variant objects
            $table->json('usage_guidelines')->nullable(); // array of guidelines
            $table->timestamps();

            // Indexes
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_primary']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_logos');
    }
};
