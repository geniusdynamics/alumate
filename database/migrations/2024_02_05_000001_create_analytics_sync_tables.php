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
        Schema::create('sync_histories', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('target');
            $table->date('date_range_start')->nullable();
            $table->date('date_range_end')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('records_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->string('tenant_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['source', 'target']);
        });

        Schema::create('discrepancies', function (Blueprint $table) {
            $table->id();
            $table->string('discrepancy_id')->unique();
            $table->string('metric');
            $table->string('source1');
            $table->string('source2');
            $table->decimal('value1', 15, 2)->default(0);
            $table->decimal('value2', 15, 2)->default(0);
            $table->decimal('difference_percentage', 5, 2)->default(0);
            $table->enum('severity', ['low', 'medium', 'high'])->default('low');
            $table->date('date_range_start')->nullable();
            $table->date('date_range_end')->nullable();
            $table->string('tenant_id')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution')->nullable();
            $table->decimal('resolved_value', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'resolved']);
            $table->index(['tenant_id', 'severity']);
            $table->index(['metric', 'severity']);
            $table->index(['discrepancy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancies');
        Schema::dropIfExists('sync_histories');
    }
};
