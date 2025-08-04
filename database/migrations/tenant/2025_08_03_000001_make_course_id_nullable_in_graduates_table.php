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
        Schema::table('graduates', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['course_id']);
            
            // Make course_id nullable
            $table->foreignId('course_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['course_id']);
            
            // Make course_id not nullable again
            $table->foreignId('course_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint with cascade delete
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });
    }
};
