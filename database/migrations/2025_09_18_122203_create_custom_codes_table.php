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
        Schema::create('custom_codes', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('page_id')->nullable()->index();
            $table->enum('type', ['html', 'css', 'javascript'])->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('code');
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_draft')->default(true)->index();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints (commented out for now - add after confirming table names)
            // $table->foreign('page_id')->references('id')->on('landing_pages')->onDelete('cascade');
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['tenant_id', 'type', 'is_active']);
            $table->index(['tenant_id', 'page_id']);
            $table->index(['created_at', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_codes');
    }
};
