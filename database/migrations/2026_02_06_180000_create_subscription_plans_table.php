<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('stripe_price_id')->nullable()->unique();
            $table->string('stripe_product_id')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->string('currency', 3)->default('usd');
            $table->string('interval')->default('monthly'); // monthly, yearly
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('trial_days')->default(14);
            $table->json('features')->nullable(); // Plan features list
            $table->json('limits')->nullable(); // Usage limits
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
