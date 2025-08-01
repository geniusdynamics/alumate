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
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['one_time', 'recurring', 'endowment']);
            $table->enum('status', ['draft', 'active', 'paused', 'closed']);
            $table->json('eligibility_criteria');
            $table->json('application_requirements');
            $table->date('application_deadline');
            $table->date('award_date')->nullable();
            $table->integer('max_recipients')->default(1);
            $table->decimal('total_fund_amount', 12, 2)->default(0);
            $table->decimal('awarded_amount', 12, 2)->default(0);
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'application_deadline']);
            $table->index(['creator_id', 'status']);
            $table->index(['institution_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
