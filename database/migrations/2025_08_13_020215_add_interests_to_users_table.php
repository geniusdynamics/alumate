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
        Schema::table('users', function (Blueprint $table) {
            $table->json('interests')->nullable()->after('language');
            $table->string('avatar_url')->nullable()->after('avatar');
            $table->text('bio')->nullable()->after('avatar_url');
            $table->string('location')->nullable()->after('bio');
            $table->string('website')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['interests', 'avatar_url', 'bio', 'location', 'website']);
        });
    }
};
