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
        Schema::create('data_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('data'); // data, schema, content, configuration
            $table->string('status')->default('pending'); // pending, processing, completed, failed, rolled_back
            $table->string('source_version');
            $table->string('target_version');
            $table->json('migration_data')->nullable();
            $table->boolean('rollback_enabled')->default(true);
            $table->boolean('dry_run')->default(false);
            $table->json('schedule')->nullable();
            $table->json('dependencies')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['source_version', 'target_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_migrations');
    }
};
