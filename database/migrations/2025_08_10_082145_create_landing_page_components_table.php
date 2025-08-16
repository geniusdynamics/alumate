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
        Schema::create('landing_page_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // hero, form, testimonial, features, etc.
            $table->text('description')->nullable();
            $table->json('default_props'); // Default component properties
            $table->json('schema'); // Component schema/configuration
            $table->string('icon')->nullable(); // Icon for builder UI
            $table->string('category')->default('general'); // Component category
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_components');
    }
};
