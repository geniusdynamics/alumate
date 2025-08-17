<?php

namespace Database\Seeders;

use App\Models\CustomReport;
use App\Models\KpiDefinition;
use App\Models\PredictionModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    public function run()
    {
        $this->seedKpiDefinitions();
        $this->seedPredictionModels();
        $this->seedSampleReports();
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
                            ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed'],
                        ],
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => [],
                    ],
                ],
                'target_type' => 'minimum',
                'target_value' => 80.0,
                'warning_threshold' => 70.0,
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
                            ['field' => 'status', 'operator' => '=', 'value' => 'hired'],
                        ],
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\JobApplication',
                        'filters' => [],
                    ],
                ],
                'target_type' => 'minimum',
                'target_value' => 25.0,
                'warning_threshold' => 15.0,
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
                            ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed'],
                        ],
                    ],
                ],
                'target_type' => 'maximum',
                'target_value' => 90.0,
                'warning_threshold' => 120.0,
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
                            ['field' => 'graduation_date', 'operator' => '!=', 'value' => null],
                        ],
                    ],
                    'denominator' => [
                        'model' => 'App\\Models\\Graduate',
                        'filters' => [],
                    ],
                ],
                'target_type' => 'minimum',
                'target_value' => 85.0,
                'warning_threshold' => 75.0,
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
                            ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                        ],
                    ],
                ],
                'target_type' => 'minimum',
                'target_value' => 50.0,
                'warning_threshold' => 25.0,
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
                        'filters' => [],
                    ],
                ],
                'target_type' => 'minimum',
                'target_value' => 4.0,
                'warning_threshold' => 3.5,
            ],
        ];

        foreach ($kpis as $kpiData) {
            KpiDefinition::updateOrCreate(
                ['key' => $kpiData['key']],
                $kpiData
            );
        }
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
                    'job_applications_count',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'graduation_year' => 0.05,
                        'course_employment_rate' => 0.25,
                        'gpa' => 0.15,
                        'skills_count' => 0.15,
                        'certifications_count' => 0.10,
                        'profile_completion' => 0.15,
                        'job_applications_count' => 0.15,
                    ],
                    'max_score' => 100,
                    'prediction_horizon' => 90,
                    'retraining_interval' => 30,
                    'prediction_refresh_days' => 7,
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
                    'skills_count',
                    'profile_completion',
                    'course_employment_rate',
                    'gpa',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'job_applications_count' => 0.10,
                        'interview_count' => 0.30,
                        'skills_count' => 0.20,
                        'profile_completion' => 0.15,
                        'course_employment_rate' => 0.15,
                        'gpa' => 0.10,
                    ],
                    'max_score' => 50,
                    'prediction_horizon' => 30,
                    'retraining_interval' => 14,
                    'prediction_refresh_days' => 3,
                ],
                'accuracy' => 0.68,
                'is_active' => true,
            ],
            [
                'name' => 'Course Demand Predictor',
                'type' => 'course_demand',
                'description' => 'Predicts future demand for courses based on job market trends',
                'features' => [
                    'graduates_count',
                    'employment_rate',
                    'industry_growth',
                    'job_postings_trend',
                    'salary_trend',
                ],
                'model_config' => [
                    'feature_weights' => [
                        'graduates_count' => 0.15,
                        'employment_rate' => 0.25,
                        'industry_growth' => 0.30,
                        'job_postings_trend' => 0.20,
                        'salary_trend' => 0.10,
                    ],
                    'max_score' => 200,
                    'prediction_horizon' => 180,
                    'retraining_interval' => 60,
                    'prediction_refresh_days' => 30,
                ],
                'accuracy' => 0.62,
                'is_active' => true,
            ],
        ];

        foreach ($models as $modelData) {
            PredictionModel::updateOrCreate(
                ['type' => $modelData['type']],
                $modelData
            );
        }
    }

    private function seedSampleReports()
    {
        // Get the first admin user for sample reports
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'super-admin');
        })->first();

        if (! $adminUser) {
            $adminUser = User::first();
        }

        if (! $adminUser) {
            return; // No users to create reports for
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
                'is_scheduled' => true,
                'schedule_frequency' => 'monthly',
                'schedule_config' => [
                    'format' => 'excel',
                    'notify_on_completion' => true,
                    'parameters' => [],
                ],
                'is_public' => true,
            ],
            [
                'user_id' => $adminUser->id,
                'name' => 'Course Performance Analysis',
                'description' => 'Analysis of course performance metrics and employment outcomes',
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
                'is_scheduled' => true,
                'schedule_frequency' => 'weekly',
                'schedule_config' => [
                    'format' => 'csv',
                    'notify_on_completion' => true,
                    'parameters' => [],
                ],
                'is_public' => true,
            ],
            [
                'user_id' => $adminUser->id,
                'name' => 'Job Market Trends',
                'description' => 'Current job market trends and opportunities',
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
                'is_scheduled' => false,
                'is_public' => true,
            ],
        ];

        foreach ($reports as $reportData) {
            CustomReport::create($reportData);
        }
    }
}
