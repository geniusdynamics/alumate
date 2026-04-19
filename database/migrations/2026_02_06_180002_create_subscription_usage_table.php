<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('feature_key'); // e.g., 'job_postings', 'storage_gb'
            $table->integer('usage')->default(0);
            $table->integer('limit')->default(0);
            $table->timestamp('period_starts_at')->nullable();
            $table->timestamp('period_ends_at')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'feature_key']);
            $table->index(['subscription_id', 'period_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};
