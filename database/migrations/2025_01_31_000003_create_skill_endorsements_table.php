<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skill_endorsements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_skill_id')->constrained()->onDelete('cascade');
            $table->foreignId('endorser_id')->constrained('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->unique(['user_skill_id', 'endorser_id']);
            $table->index('endorser_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('skill_endorsements');
    }
};
