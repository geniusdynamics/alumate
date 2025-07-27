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
        Schema::create('graduate_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('graduate_id'); // No foreign key constraint since graduates are tenant-specific
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // 'created', 'updated', 'employment_updated', 'privacy_updated'
            $table->string('field_name')->nullable(); // specific field that was changed
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description'); // human-readable description
            $table->json('metadata')->nullable(); // additional context
            $table->timestamps();
            
            $table->index(['graduate_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('graduate_audit_logs');
    }
};
