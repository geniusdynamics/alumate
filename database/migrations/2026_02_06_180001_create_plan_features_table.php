<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->string('feature_key'); // e.g., 'job_postings', 'storage_gb', 'alumni_import'
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->string('value_type')->default('boolean'); // boolean, number, string
            $table->string('value'); // The feature value (unlimited, 100, true, etc.)
            $table->string('display_format')->nullable(); // How to display the value
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
            $table->index('feature_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
