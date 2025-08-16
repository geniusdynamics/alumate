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
        Schema::create('homepage_content_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_content_id')->constrained('homepage_content')->onDelete('cascade');
            $table->integer('version_number');
            $table->text('value'); // The content value for this version
            $table->json('metadata')->nullable(); // Metadata for this version
            $table->text('change_notes')->nullable(); // Notes about what changed
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['homepage_content_id', 'version_number']);
            $table->index(['homepage_content_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_content_versions');
    }
};
