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
        Schema::table('sequence_enrollments', function (Blueprint $table) {
            $table->foreign('sequence_id')->references('id')->on('email_sequences')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sequence_enrollments', function (Blueprint $table) {
            $table->dropForeign(['sequence_id']);
        });
    }
};