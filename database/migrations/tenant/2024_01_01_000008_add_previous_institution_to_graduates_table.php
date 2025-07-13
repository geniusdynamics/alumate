<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->string('previous_institution_id')->nullable();
            $table->foreign('previous_institution_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropForeign(['previous_institution_id']);
            $table->dropColumn('previous_institution_id');
        });
    }
};
