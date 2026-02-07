<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'name' => 'Free',
                'description' => 'Perfect for getting started with basic alumni management.',
                'price_monthly' => 0,
                'price_yearly' => null,
                'is_active' => true,
                'is_popular' => false,
                'trial_days' => 0,
                'display_order' => 1,
                'features' => [
                    'Up to 100 alumni profiles',
                    'Basic job board',
                    'Email notifications',
                    'Community access',
                ],
                'plan_features' => [
                    ['feature_key' => 'max_alumni', 'feature_name' => 'Alumni Profiles', 'value_type' => 'number', 'value' => '100', 'display_format' => '%s profiles'],
                    ['feature_key' => 'max_job_postings', 'feature_name' => 'Job Postings', 'value_type' => 'number', 'value' => '5', 'display_format' => '%s active'],
                    ['feature_key' => 'max_admins', 'feature_name' => 'Admin Users', 'value_type' => 'number', 'value' => '1', 'display_format' => '%s user'],
                    ['feature_key' => 'storage_gb', 'feature_name' => 'Storage', 'value_type' => 'number', 'value' => '1', 'display_format' => '%s GB'],
                    ['feature_key' => 'custom_branding', 'feature_name' => 'Custom Branding', 'value_type' => 'boolean', 'value' => 'false'],
                    ['feature_key' => 'analytics', 'feature_name' => 'Analytics Dashboard', 'value_type' => 'boolean', 'value' => 'basic'],
                    ['feature_key' => 'support', 'feature_name' => 'Support', 'value_type' => 'string', 'value' => 'Community', 'display_format' => '%s'],
                    ['feature_key' => 'api_access', 'feature_name' => 'API Access', 'value_type' => 'boolean', 'value' => 'false'],
                    ['feature_key' => 'sso', 'feature_name' => 'SSO Integration', 'value_type' => 'boolean', 'value' => 'false'],
                ],
            ],
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'Ideal for small institutions starting their alumni network.',
                'price_monthly' => 49,
                'price_yearly' => 490,
                'is_active' => true,
                'is_popular' => false,
                'trial_days' => 14,
                'display_order' => 2,
                'features' => [
                    'Up to 1,000 alumni profiles',
                    'Advanced job board',
                    'Email campaigns',
                    'Basic analytics',
                    'Priority support',
                ],
                'plan_features' => [
                    ['feature_key' => 'max_alumni', 'feature_name' => 'Alumni Profiles', 'value_type' => 'number', 'value' => '1000', 'display_format' => '%s profiles'],
                    ['feature_key' => 'max_job_postings', 'feature_name' => 'Job Postings', 'value_type' => 'number', 'value' => '25', 'display_format' => '%s active'],
                    ['feature_key' => 'max_admins', 'feature_name' => 'Admin Users', 'value_type' => 'number', 'value' => '3', 'display_format' => '%s users'],
                    ['feature_key' => 'storage_gb', 'feature_name' => 'Storage', 'value_type' => 'number', 'value' => '10', 'display_format' => '%s GB'],
                    ['feature_key' => 'custom_branding', 'feature_name' => 'Custom Branding', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'analytics', 'feature_name' => 'Analytics Dashboard', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'support', 'feature_name' => 'Support', 'value_type' => 'string', 'value' => 'Priority Email', 'display_format' => '%s'],
                    ['feature_key' => 'api_access', 'feature_name' => 'API Access', 'value_type' => 'boolean', 'value' => 'false'],
                    ['feature_key' => 'sso', 'feature_name' => 'SSO Integration', 'value_type' => 'boolean', 'value' => 'false'],
                ],
            ],
            [
                'slug' => 'professional',
                'name' => 'Professional',
                'description' => 'Complete solution for growing alumni programs.',
                'price_monthly' => 149,
                'price_yearly' => 1490,
                'is_active' => true,
                'is_popular' => true,
                'trial_days' => 14,
                'display_order' => 3,
                'features' => [
                    'Up to 10,000 alumni profiles',
                    'Unlimited job postings',
                    'Advanced email campaigns',
                    'Full analytics suite',
                    'Custom branding',
                    'API access',
                    'Dedicated support',
                ],
                'plan_features' => [
                    ['feature_key' => 'max_alumni', 'feature_name' => 'Alumni Profiles', 'value_type' => 'number', 'value' => '10000', 'display_format' => '%s profiles'],
                    ['feature_key' => 'max_job_postings', 'feature_name' => 'Job Postings', 'value_type' => 'string', 'value' => 'unlimited', 'display_format' => 'Unlimited'],
                    ['feature_key' => 'max_admins', 'feature_name' => 'Admin Users', 'value_type' => 'number', 'value' => '10', 'display_format' => '%s users'],
                    ['feature_key' => 'storage_gb', 'feature_name' => 'Storage', 'value_type' => 'number', 'value' => '100', 'display_format' => '%s GB'],
                    ['feature_key' => 'custom_branding', 'feature_name' => 'Custom Branding', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'analytics', 'feature_name' => 'Analytics Dashboard', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'support', 'feature_name' => 'Support', 'value_type' => 'string', 'value' => 'Dedicated', 'display_format' => '%s'],
                    ['feature_key' => 'api_access', 'feature_name' => 'API Access', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'sso', 'feature_name' => 'SSO Integration', 'value_type' => 'boolean', 'value' => 'false'],
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Full-featured solution for large institutions with advanced needs.',
                'price_monthly' => 499,
                'price_yearly' => 4990,
                'is_active' => true,
                'is_popular' => false,
                'trial_days' => 30,
                'display_order' => 4,
                'features' => [
                    'Unlimited alumni profiles',
                    'Unlimited everything',
                    'White-label options',
                    'SSO integration',
                    'Custom development',
                    'SLA guarantee',
                    'Account manager',
                ],
                'plan_features' => [
                    ['feature_key' => 'max_alumni', 'feature_name' => 'Alumni Profiles', 'value_type' => 'string', 'value' => 'unlimited', 'display_format' => 'Unlimited'],
                    ['feature_key' => 'max_job_postings', 'feature_name' => 'Job Postings', 'value_type' => 'string', 'value' => 'unlimited', 'display_format' => 'Unlimited'],
                    ['feature_key' => 'max_admins', 'feature_name' => 'Admin Users', 'value_type' => 'string', 'value' => 'unlimited', 'display_format' => 'Unlimited'],
                    ['feature_key' => 'storage_gb', 'feature_name' => 'Storage', 'value_type' => 'string', 'value' => 'unlimited', 'display_format' => 'Unlimited'],
                    ['feature_key' => 'custom_branding', 'feature_name' => 'Custom Branding', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'analytics', 'feature_name' => 'Analytics Dashboard', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'support', 'feature_name' => 'Support', 'value_type' => 'string', 'value' => '24/7 Premium', 'display_format' => '%s'],
                    ['feature_key' => 'api_access', 'feature_name' => 'API Access', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'sso', 'feature_name' => 'SSO Integration', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'sla', 'feature_name' => 'SLA Guarantee', 'value_type' => 'boolean', 'value' => 'true'],
                    ['feature_key' => 'custom_dev', 'feature_name' => 'Custom Development', 'value_type' => 'boolean', 'value' => 'true'],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $planFeatures = $planData['plan_features'] ?? [];
            unset($planData['plan_features']);

            $plan = SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            // Create plan features
            foreach ($planFeatures as $feature) {
                PlanFeature::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_key' => $feature['feature_key'],
                    ],
                    array_merge($feature, ['plan_id' => $plan->id])
                );
            }
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}
