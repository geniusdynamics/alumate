<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Only create if it doesn't exist
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('logo_url')->nullable();
                $table->string('website')->nullable();
                $table->enum('size', ['startup', 'small', 'medium', 'large', 'enterprise'])->nullable();
                $table->string('industry')->nullable();
                $table->string('location')->nullable();
                $table->integer('founded_year')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
                $table->softDeletes();

                // Indexes for performance
                $table->index('name');
                $table->index('industry');
                $table->index('location');
                $table->index('is_verified');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
};