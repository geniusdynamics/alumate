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
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('form_builders')->onDelete('cascade');
            $table->string('field_type');
            $table->string('field_name');
            $table->string('field_label');
            $table->string('field_placeholder')->nullable();
            $table->json('field_options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('conditional_logic')->nullable();
            $table->integer('order_index')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->json('crm_field_mapping')->nullable();
            $table->timestamps();
            
            $table->index(['form_id', 'order_index']);
            $table->index(['field_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
