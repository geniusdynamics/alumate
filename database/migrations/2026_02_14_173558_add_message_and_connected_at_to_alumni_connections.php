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
        Schema::table('alumni_connections', function (Blueprint $table) {
            $table->text('message')->nullable()->after('status');
            $table->timestamp('connected_at')->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumni_connections', function (Blueprint $table) {
            $table->dropColumn(['message', 'connected_at']);
        });
    }
};
