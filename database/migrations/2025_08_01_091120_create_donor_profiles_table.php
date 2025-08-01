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
        Schema::create('donor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('donor_tier', ['prospect', 'major', 'principal', 'legacy'])->default('prospect');
            $table->decimal('lifetime_giving', 15, 2)->default(0);
            $table->decimal('largest_gift', 15, 2)->default(0);
            $table->decimal('capacity_rating', 15, 2)->nullable();
            $table->decimal('inclination_score', 3, 2)->nullable(); // 0.00 to 1.00
            $table->json('giving_interests')->nullable(); // Areas of interest
            $table->json('preferred_contact_methods')->nullable();
            $table->string('preferred_contact_frequency')->nullable();
            $table->date('last_contact_date')->nullable();
            $table->date('next_contact_date')->nullable();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('wealth_indicators')->nullable();
            $table->json('relationship_connections')->nullable(); // Board members, other donors, etc.
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('do_not_contact')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['donor_tier', 'lifetime_giving']);
            $table->index(['assigned_officer_id', 'next_contact_date']);
            $table->index(['capacity_rating', 'inclination_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_profiles');
    }
};
