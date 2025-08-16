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
        Schema::create('homepage_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('target')->default('_self');
            $table->foreignId('parent_id')->nullable()->constrained('homepage_navigation_items')->onDelete('cascade');
            $table->unsignedInteger('order')->default(0);
            $table->string('type')->default('link'); // e.g., link, dropdown, button
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_navigation_items');
    }
};
