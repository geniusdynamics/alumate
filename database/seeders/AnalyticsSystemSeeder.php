<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KpiDefinition;
use App\Models\PredictionModel;
use App\Models\CustomReport;
use App\Models\User;

class AnalyticsSystemSeeder extends Seeder
{
    public function run()
    {
        $this->seedKpiDefinitions();
        $this->seedPredictionModels();
        $this->seedDefaultReports();
    }

    private function seedKpiDefinitions()
    {
        $kpis = [
            [
                'name' => 'Employment Rate',
                'key' => 'employment_rate',
                'description' => 'Percentage of graduates who are currently employed',
                'category' => 'employment',
                'calculation_method' => 'percentage',
                'calculation_config' => [
                    'numerator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => [
                            ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed']
                        ]
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => []
                    ]
                ],
                'target_type' => 'minimum',
                'target_value' => 80.0,
                'warning_threshold' => 70.0,
                'is_active' => true,
            ],
            [
                'name' => 'Job Placement Rate',
                'key' => 'job_placement_rate',
                'description' => 'Percentage of job applications that result in employment',
                'category' => 'employment',
                'calculation_method' => 'percentage',
                'calculation_config' => [
                    'numerator' => [
                        'model' => 'App\\Models\\JobApplication',
                        'filters' => [
                            ['field' => 'status', 'operator' => '=', 'value' => 'hired']
                        ]
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\JobApplication',
                        'filters' => []
                    ]
                ],
                'target_type' => 'minimum',
                'target_value' => 25.0,
                'warning_threshold' => 15.0,
                'is_active' => true,
            ],
            [
                'name' => 'Average Time to Employment',
                'key' => 'avg_time_to_employment',
                'description' => 'Average number of days from graduation to first employment',
                'category' => 'employment',
                'calculation_method' => 'average',
                'calculation_config' => [
                    'query' => [
                        'model' => 'App\\Models\\Graduate',
                        'field' => 'days_to_employment',
                        'filters' => [
                            ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed']
                        ]
                    ]
                ],
                'target_type' => 'maximum',
                'target_value' => 90.0,
                'warning_threshold' => 120.0,
                'is_active' => true,
            ],
            [
                'name' => 'Course Completion Rate',
                'key' => 'course_completion_rate',
                'description' => 'Percentage of enrolled students who complete their courses',
                'category' => 'academic',
                'calculation_method' => 'percentage',
                'calculation_config' => [
                    'numerator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => [
                            ['field' => 'graduation_date', 'operator' => '!=', 'value' => null]
                        ]
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => []
                    ]
                ],
                'target_type' => 'minimum',
                'target_value' => 85.0,
                'warning_threshold' => 75.0,
                'is_active' => true,
            ],
            [
                'name' => 'Active Job Postings',
                'key' => 'active_job_postings',
                'description' => 'Number of currently active job postings',
                'category' => 'operational',
                'calculation_method' => 'count',
                'calculation_config' => [
                    'query' => [
                        'model' => 'App\\Models\\Job',
                        'filters' => [
                            ['field' => 'status', 'operator' => '=', 'value' => 'active']
                        ]
                    ]
                ],
                'target_type' => 'minimum',
                'target_value' => 50.0,
                'warning_threshold' => 25.0,
                'is_active' => true,
            ],
            [
                'name' => 'Employer Satisfaction Rate',
                'key' => 'employer_satisfaction_rate',
                'description' => 'Average rating given by employers',
                'category' => 'satisfaction',
                'calculation_method' => 'average',
                'calculation_config' => [
                    'query' => [
                        'model' => 'App\\Models\\EmployerRating',
                        'field' => 'rating',
                        'filters' => []
                    ]
                ],
                'target_type' => 'minimum',
                'target_value' => 4.0,
                'warning_threshold' => 3.5,
                'is_active' => true,
            ],
        ];

        foreach ($kpis as $kpiData) {
            KpiDefinition::updateOrCreate(
                ['key' => $kpiData['key']],
                $kpiData
            );
        }

        $this->command->info('KPI definitions seeded successfully.');
    }

    private function seedPredictionModels()
    {
        $models = [
            [
                'name' => 'Job Placement Predictor',
                'type' => 'job_placement',
                'description' => 'Predicts the likelihood of a graduate finding employment within 90 days',
                'features' => [
                    'graduation_year',
                    'course_employment_rate',
                    'gpa',
                    'skills_count',
                    'certifications_count',
                    'profile_completion',
                    'location_job_market',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'graduation_year' => 0.1,
                        'course_employment_rate' => 0.3,
                        'gpa' => 0.2,
                        'skills_count' => 0.15,
                        'certifications_count' => 0.1,
                        'profile_completion' => 0.1,
                        'location_job_market' => 0.05,
                    ],
                    'max_score' => 100,
                    'prediction_horizon' => 90,
                    'retraining_interval' => 30,
                    'min_confidence_threshold' => 0.6,
                ],
                'accuracy' => 0.75,
                'is_active' => true,
            ],
            [
                'name' => 'Application Success Predictor',
                'type' => 'employment_success',
                'description' => 'Predicts the likelihood of a job application being successful',
                'features' => [
                    'job_applications_count',
                    'interview_count',
                    'skills_match_score',
                    'profile_completion',
                    'course_employment_rate',
                    'application_timing',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'job_applications_count' => 0.1,
                        'interview_count' => 0.3,
                        'skills_match_score' => 0.25,
                        'profile_completion' => 0.15,
                        'course_employment_rate' => 0.15,
                        'application_timing' => 0.05,
                    ],
                    'max_score' => 50,
                    'prediction_horizon' => 30,
                    'retraining_interval' => 14,
                    'min_confidence_threshold' => 0.5,
                ],
                'accuracy' => 0.68,
                'is_active' => true,
            ],
            [
                'name' => 'Course Demand Predictor',
                'type' => 'course_demand',
                'description' => 'Predicts future demand for courses based on job market trends',
                'features' => [
                    'historical_enrollment',
                    'employment_rate',
                    'job_postings_trend',
                    'industry_growth_rate',
                    'salary_trends',
                    'skills_demand',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'historical_enrollment' => 0.2,
                        'employment_rate' => 0.25,
                        'job_postings_trend' => 0.2,
                        'industry_growth_rate' => 0.15,
                        'salary_trends' => 0.1,
                        'skills_demand' => 0.1,
                    ],
                    'max_score' => 200,
                    'prediction_horizon' => 180,
                    'retraining_interval' => 60,
                    'min_confidence_threshold' => 0.7,
                ],
                'accuracy' => 0.72,
                'is_active' => true,
            ],
        ];

        foreach ($models as $modelData) {
            PredictionModel::updateOrCreate(
                ['type' => $modelData['type']],
                $modelData
            );
        }

        $this->command->info('Prediction models seeded successfully.');
    }

    private function seedDefaultReports()
    {
        // Get the first admin user or create a system user
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'super-admin');
        })->first();

        if (!$adminUser) {
            $adminUser = User::first();
        }

        if (!$adminUser) {
            $this->command->warn('No users found. Skipping default reports seeding.');
            return;
        }

        $reports = [
            [
                'user_id' => $adminUser->id,
                'name' => 'Monthly Employment Report',
                'description' => 'Comprehensive monthly report on graduate employment status',
                'type' => 'employment',
                'filters' => [
                    'date_range' => [
                        'start' => now()->startOfMonth()->toDateString(),
                        'end' => now()->endOfMonth()->toDateString(),
                    ],
                ],
                'columns' => [
                    'graduate_name',
                    'course_name',
                    'graduation_date',
                    'employment_status',
                    'company_name',
                    'job_title',
                    'salary_range',
                ],
                'chart_config' => [
                    'type' => 'bar',
                    'x_axis' => 'course_name',
                    'y_axis' => 'employment_rate',
                ],
                'is_scheduled' => true,
                'schedule_frequency' => 'monthly',
                'schedule_config' => [
                    'day_of_month' => 1,
                    'time' => '09:00',
                    'format' => 'excel',
                    'delivery' => [
                        'method' => 'email',
                        'recipients' => [$adminUser->email],
                    ],
                ],
                'is_public' => true,
            ],
            [
                'user_id' => $adminUser->id,
                'name' => 'Course Performance Dashboard',
                'description' => 'Weekly analysis of course performance metrics',
                'type' => 'course_performance',
                'filters' => [],
                'columns' => [
                    'course_name',
                    'total_graduates',
                    'employed_count',
                    'employment_rate',
                    'average_salary',
                    'top_employers',
                ],
                'chart_config' => [
                    'type' => 'line',
                    'x_axis' => 'course_name',
                    'y_axis' => 'employment_rate',
                ],
                'is_scheduled' => true,
                'schedule_frequency' => 'weekly',
                'schedule_config' => [
                    'day_of_week' => 1, // Monday
                    'time' => '08:00',
                    'format' => 'pdf',
                ],
                'is_public' => false,
            ],
            [
                'user_id' => $adminUser->id,
                'name' => 'Job Market Analysis',
                'description' => 'Analysis of job market trends and opportunities',
                'type' => 'job_market',
                'filters' => [
                    'date_range' => [
                        'start' => now()->subDays(30)->toDateString(),
                        'end' => now()->toDateString(),
                    ],
                ],
                'columns' => [
                    'job_title',
                    'company_name',
                    'location',
                    'salary_range',
                    'required_skills',
                    'application_count',
                    'posted_date',
                ],
                'chart_config' => [
                    'type' => 'pie',
                    'category' => 'location',
                    'value' => 'job_count',
                ],
                'is_scheduled' => false,
                'schedule_frequency' => null,
                'schedule_config' => null,
                'is_public' => true,
            ],
        ];

        foreach ($reports as $reportData) {
            CustomReport::create($reportData);
        }

        $this->command->info('Default reports seeded successfully.');
    }
}