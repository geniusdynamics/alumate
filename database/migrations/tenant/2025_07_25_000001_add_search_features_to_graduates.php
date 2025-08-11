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
        // Add search-related columns to graduates table
        Schema::table('graduates', function (Blueprint $table) {
            $table->integer('profile_views')->default(0)->after('profile_completion_percentage');
            $table->integer('search_appearances')->default(0)->after('profile_views');
            $table->decimal('avg_match_score', 5, 2)->nullable()->after('search_appearances');
            $table->json('search_keywords')->nullable()->after('avg_match_score');

            $table->index('profile_views');
            $table->index('search_appearances');
            $table->index('avg_match_score');
        });

        // Create job_graduate_matches table for storing calculated matches
        Schema::create('job_graduate_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id'); // Don't use foreign key constraint for cross-database reference
            $table->foreignId('graduate_id')->constrained()->onDelete('cascade');
            $table->decimal('match_score', 5, 2);
            $table->json('match_factors');
            $table->decimal('compatibility_score', 5, 2)->nullable();
            $table->json('compatibility_factors')->nullable();
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_viewed')->default(false);
            $table->boolean('is_applied')->default(false);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['job_id', 'graduate_id']);
            $table->index(['job_id', 'match_score']);
            $table->index(['graduate_id', 'match_score']);
            $table->index(['is_recommended', 'match_score']);
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_graduate_matches');

        Schema::table('graduates', function (Blueprint $table) {
            $table->dropIndex(['profile_views']);
            $table->dropIndex(['search_appearances']);
            $table->dropIndex(['avg_match_score']);
            $table->dropColumn(['profile_views', 'search_appearances', 'avg_match_score', 'search_keywords']);
        });
    }
};
