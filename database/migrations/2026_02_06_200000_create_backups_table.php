<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // database, files
            $table->string('subtype')->default('manual'); // manual, scheduled, incremental
            $table->string('filename');
            $table->string('path');
            $table->string('cloud_path')->nullable();
            $table->string('cloud_disk')->nullable();
            $table->bigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_status')->nullable(); // valid, invalid
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
