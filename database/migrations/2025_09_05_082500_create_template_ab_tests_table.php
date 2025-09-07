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
        // Check if the template_ab_tests table exists
        if (Schema::hasTable('template_ab_tests')) {
            Schema::table('template_ab_tests', function (Blueprint $table) {
                // Add new columns if they don't exist
                if (!Schema::hasColumn('template_ab_tests', 'template_id')) {
                    $table->foreignId('template_id')->constrained('templates')->onDelete('cascade')->after('id');
                }
                if (!Schema::hasColumn('template_ab_tests', 'variants')) {
                    $table->json('variants')->after('description'); // Array of variant configurations
                }
                if (!Schema::hasColumn('template_ab_tests', 'goal_metric')) {
                    $table->string('goal_metric')->default('conversion_rate')->after('status'); // conversion_rate, click_rate, time_on_page
                }
                if (!Schema::hasColumn('template_ab_tests', 'confidence_threshold')) {
                    $table->decimal('confidence_threshold', 5, 4)->default(0.95)->after('goal_metric'); // Statistical significance threshold
                }
                if (!Schema::hasColumn('template_ab_tests', 'sample_size_per_variant')) {
                    $table->integer('sample_size_per_variant')->default(1000)->after('confidence_threshold');
                }
                if (!Schema::hasColumn('template_ab_tests', 'traffic_distribution')) {
                    $table->json('traffic_distribution')->nullable()->after('sample_size_per_variant'); // Manual traffic split percentages
                }
                if (!Schema::hasColumn('template_ab_tests', 'started_at')) {
                    $table->timestamp('started_at')->nullable()->after('traffic_distribution');
                }
                if (!Schema::hasColumn('template_ab_tests', 'ended_at')) {
                    $table->timestamp('ended_at')->nullable()->after('started_at');
                }
                if (!Schema::hasColumn('template_ab_tests', 'results')) {
                    $table->json('results')->nullable()->after('ended_at'); // Statistical analysis results
                }

                // Indexes
                if (!Schema::hasIndex('template_ab_tests', 'template_ab_tests_template_id_status_index')) {
                    $table->index(['template_id', 'status']);
                }
                if (!Schema::hasIndex('template_ab_tests', 'template_ab_tests_status_started_at_index')) {
                    $table->index(['status', 'started_at']);
                }
                if (!Schema::hasIndex('template_ab_tests', 'template_ab_tests_goal_metric_index')) {
                    $table->index('goal_metric');
                }
            });
        } else {
            // Create the template_ab_tests table if it doesn't exist
            Schema::create('template_ab_tests', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('variants'); // Array of variant configurations
                $table->string('status')->default('draft'); // draft, active, paused, completed
                $table->string('goal_metric')->default('conversion_rate'); // conversion_rate, click_rate, time_on_page
                $table->decimal('confidence_threshold', 5, 4)->default(0.95); // Statistical significance threshold
                $table->integer('sample_size_per_variant')->default(1000);
                $table->json('traffic_distribution')->nullable(); // Manual traffic split percentages
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->json('results')->nullable(); // Statistical analysis results
                $table->timestamps();

                // Foreign keys
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

                // Indexes
                $table->index(['template_id', 'status']);
                $table->index(['status', 'started_at']);
                $table->index('goal_metric');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_ab_tests', function (Blueprint $table) {
            // Drop added columns if they exist
            if (Schema::hasColumn('template_ab_tests', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            }
            if (Schema::hasColumn('template_ab_tests', 'variants')) {
                $table->dropColumn('variants');
            }
            if (Schema::hasColumn('template_ab_tests', 'goal_metric')) {
                $table->dropColumn('goal_metric');
            }
            if (Schema::hasColumn('template_ab_tests', 'confidence_threshold')) {
                $table->dropColumn('confidence_threshold');
            }
            if (Schema::hasColumn('template_ab_tests', 'sample_size_per_variant')) {
                $table->dropColumn('sample_size_per_variant');
            }
            if (Schema::hasColumn('template_ab_tests', 'traffic_distribution')) {
                $table->dropColumn('traffic_distribution');
            }
            if (Schema::hasColumn('template_ab_tests', 'started_at')) {
                $table->dropColumn('started_at');
            }
            if (Schema::hasColumn('template_ab_tests', 'ended_at')) {
                $table->dropColumn('ended_at');
            }
            if (Schema::hasColumn('template_ab_tests', 'results')) {
                $table->dropColumn('results');
            }

            // Drop indexes
            if (Schema::hasIndex('template_ab_tests', 'template_ab_tests_template_id_status_index')) {
                $table->dropIndex(['template_id', 'status']);
            }
            if (Schema::hasIndex('template_ab_tests', 'template_ab_tests_status_started_at_index')) {
                $table->dropIndex(['status', 'started_at']);
            }
            if (Schema::hasIndex('template_ab_tests', 'template_ab_tests_goal_metric_index')) {
                $table->dropIndex('goal_metric');
            }
        });
    }
};