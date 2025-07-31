<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Only create if it doesn't exist
        if (!Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->nullable(); // University, College, etc.
                $table->string('location')->nullable();
                $table->string('website')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('name');
                $table->index('type');
                $table->index('is_active');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('institutions');
    }
};