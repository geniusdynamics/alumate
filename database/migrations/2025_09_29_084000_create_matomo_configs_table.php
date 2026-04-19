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
        Schema::create('matomo_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('matomo_url');
            $table->string('site_id');
            $table->string('token_auth');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matomo_configs');
    }
};