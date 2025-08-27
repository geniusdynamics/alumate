<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Career Outcome Snapshots - aggregated data by time periods
        Schema::create('career_outcome_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('period_type'); // monthly, quarterly, yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->string('graduation_year')->nullable();
            $table->string('program')->nullable();
            $table->string('department')->nullable();
            $table->string('demographic_group')->nullable();
            $table->json('metrics'); // employment_rate, avg_salary, etc.
            $table->integer('total_graduates');
            $table->integer('tracked_graduates');
            $table->timestamps();

            $table->index(['period_type', 'period_start']);
            $table->index(['graduation_year', 'program']);
            $table->index(['demographic_group']);
        });

        // Salary Progression Tracking
        Schema::create('salary_progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('salary', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('salary_type'); // annual, hourly, contract
            $table->string('position_title');
            $table->string('company');
            $table->string('industry')->nullable();
            $table->date('effective_date');
            $table->integer('years_since_graduation');
            $table->json('metadata')->nullable(); // benefits, equity, etc.
            $table->timestamps();

            $table->index(['user_id', 'effective_date']);
            $table->index(['years_since_graduation']);
            $table->index(['industry']);
        });

        // Industry Placement Statistics
        Schema::create('industry_placements', function (Blueprint $table) {
            $table->id();
            $table->string('industry');
            $table->string('sub_industry')->nullable();
            $table->string('graduation_year');
            $table->string('program');
            $table->integer('placement_count');
            $table->decimal('avg_starting_salary', 12, 2)->nullable();
            $table->decimal('avg_current_salary', 12, 2)->nullable();
            $table->decimal('retention_rate', 5, 2)->nullable(); // percentage
            $table->json('top_companies')->nullable();
            $table->json('skills_in_demand')->nullable();
            $table->timestamps();

            $table->index(['industry', 'graduation_year']);
            $table->index(['program', 'graduation_year']);
        });

        // Career Path Analysis
        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('path_type'); // linear, pivot, entrepreneurial, etc.
            $table->json('progression_stages'); // array of career stages
            $table->integer('total_job_changes');
            $table->integer('promotions_count');
            $table->integer('industry_changes');
            $table->decimal('salary_growth_rate', 5, 2)->nullable();
            $table->integer('years_to_leadership')->nullable();
            $table->json('skills_evolution')->nullable();
            $table->timestamps();

            $table->index(['path_type']);
            $table->index(['user_id']);
        });

        // Program Effectiveness Metrics
        Schema::create('program_effectiveness', function (Blueprint $table) {
            $table->id();
            $table->string('program_name');
            $table->string('department');
            $table->string('graduation_year');
            $table->integer('total_graduates');
            $table->decimal('employment_rate_6_months', 5, 2);
            $table->decimal('employment_rate_1_year', 5, 2);
            $table->decimal('employment_rate_2_years', 5, 2);
            $table->decimal('avg_starting_salary', 12, 2)->nullable();
            $table->decimal('avg_salary_1_year', 12, 2)->nullable();
            $table->decimal('avg_salary_2_years', 12, 2)->nullable();
            $table->decimal('job_satisfaction_score', 3, 2)->nullable();
            $table->decimal('alumni_engagement_score', 3, 2)->nullable();
            $table->json('top_employers')->nullable();
            $table->json('skills_gaps')->nullable();
            $table->timestamps();

            $table->index(['program_name', 'graduation_year']);
            $table->index(['department', 'graduation_year']);
        });

        // Demographic Career Outcomes
        Schema::create('demographic_outcomes', function (Blueprint $table) {
            $table->id();
            $table->string('demographic_type'); // gender, ethnicity, age_group, etc.
            $table->string('demographic_value');
            $table->string('graduation_year');
            $table->string('program')->nullable();
            $table->decimal('employment_rate', 5, 2);
            $table->decimal('avg_salary', 12, 2)->nullable();
            $table->decimal('leadership_rate', 5, 2)->nullable();
            $table->decimal('entrepreneurship_rate', 5, 2)->nullable();
            $table->json('industry_distribution')->nullable();
            $table->json('challenges')->nullable();
            $table->json('success_factors')->nullable();
            $table->timestamps();

            $table->index(['demographic_type', 'demographic_value']);
            $table->index(['graduation_year', 'program']);
        });

        // Career Trend Analysis
        Schema::create('career_trends', function (Blueprint $table) {
            $table->id();
            $table->string('trend_type'); // salary, industry_shift, skill_demand, etc.
            $table->string('category'); // program, industry, demographic, etc.
            $table->string('category_value');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('trend_data'); // time series data
            $table->decimal('growth_rate', 5, 2)->nullable();
            $table->string('trend_direction'); // increasing, decreasing, stable
            $table->text('analysis')->nullable();
            $table->timestamps();

            $table->index(['trend_type', 'category']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('career_trends');
        Schema::dropIfExists('demographic_outcomes');
        Schema::dropIfExists('program_effectiveness');
        Schema::dropIfExists('career_paths');
        Schema::dropIfExists('industry_placements');
        Schema::dropIfExists('salary_progressions');
        Schema::dropIfExists('career_outcome_snapshots');
    }
};
