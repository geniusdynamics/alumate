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
        Schema::table('consents', function (Blueprint $table) {
            $table->enum('type', ['analytics'])->after('user_id');
            $table->timestamp('granted_at')->nullable()->after('type');
            $table->timestamp('revoked_at')->nullable()->after('granted_at');

            $table->index(['user_id', 'type']);
            $table->index('granted_at');
            $table->index('revoked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};