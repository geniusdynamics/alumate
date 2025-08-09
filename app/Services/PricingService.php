<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PricingService
{
    /**
     * Get pricing plans for a specific audience
     */
    public function getPlansForAudience(string $audience): array
    {
        return Cache::remember("pricing_plans_{$audience}", 3600, function () use ($audience) {
            return $audience === 'individual' 
                ? $this->getIndividualPlans() 
                : $this->getInstitutionalPlans();
        });
    }

    /**
     * Get individual alumni pricing plans
     */
    private function getIndividualPlans(): array
    {
        return [
            [
                'id' => 'free',
                'name' => 'Free',
                'description' => 'Perfect for getting started',
                'price' => 0,
                'billing_period' => '/month',
                'cta_text' => 'Start Free',
                'featured' => false,
                'features' => [
                    ['name' => 'Basic alumni directory access', 'included' => true, 'limit' => 'Limited search results'],
                    ['name' => 'Profile creation', 'included' => true],
                    ['name' => 'Basic messaging', 'included' => true, 'limit' => '5 messages/month'],
                    ['name' => 'Event notifications', 'included' => true],
                    ['name' => 'Job board access', 'included' => false],
                    ['name' => 'Mentorship matching', 'included' => false],
                    ['name' => 'Advanced networking tools', 'included' => false],
                    ['name' => 'Priority support', 'included' => false]
                ],
                'additional_info' => 'No credit card required'
            ],
            [
                'id' => 'professional',
                'name' => 'Professional',
                'description' => 'For active networkers',
                'price' => 29,
                'original_price' => 39,
                'billing_period' => '/month',
                'cta_text' => 'Start Free Trial',
                'featured' => true,
                'features' => [
                    ['name' => 'Full alumni directory access', 'included' => true],
                    ['name' => 'Advanced profile features', 'included' => true],
                    ['name' => 'Unlimited messaging', 'included' => true],
                    ['name' => 'Event creation & management', 'included' => true],
                    ['name' => 'Job board access', 'included' => true],
                    ['name' => 'Mentorship matching', 'included' => true],
                    ['name' => 'Advanced networking tools', 'included' => true],
                    ['name' => 'Priority support', 'included' => true]
                ],
                'additional_info' => '14-day free trial'
            ],
            [
                'id' => 'executive',
                'name' => 'Executive',
                'description' => 'For senior professionals',
                'price' => 79,
                'original_price' => 99,
                'billing_period' => '/month',
                'cta_text' => 'Start Free Trial',
                'featured' => false,
                'features' => [
                    ['name' => 'Everything in Professional', 'included' => true],
                    ['name' => 'Executive networking events', 'included' => true],
                    ['name' => 'Personal brand building tools', 'included' => true],
                    ['name' => 'Advanced analytics', 'included' => true],
                    ['name' => 'Concierge support', 'included' => true],
                    ['name' => 'Custom integrations', 'included' => true],
                    ['name' => 'Speaking opportunities', 'included' => true],
                    ['name' => 'Board connections', 'included' => true]
                ],
                'additional_info' => '30-day free trial'
            ]
        ];
    }

    /**
     * Get institutional pricing plans
     */
    private function getInstitutionalPlans(): array
    {
        return [
            [
                'id' => 'professional_inst',
                'name' => 'Professional',
                'description' => 'For small institutions',
                'price' => 2500,
                'billing_period' => '/month',
                'cta_text' => 'Request Demo',
                'featured' => false,
                'features' => [
                    ['name' => 'Up to 5,000 alumni', 'included' => true],
                    ['name' => 'Basic branded app', 'included' => true],
                    ['name' => 'Admin dashboard', 'included' => true],
                    ['name' => 'Basic analytics', 'included' => true],
                    ['name' => 'Email support', 'included' => true],
                    ['name' => 'Custom branding', 'included' => false],
                    ['name' => 'Advanced integrations', 'included' => false],
                    ['name' => 'Dedicated support', 'included' => false]
                ],
                'additional_info' => 'Setup fee may apply'
            ],
            [
                'id' => 'enterprise_inst',
                'name' => 'Enterprise',
                'description' => 'For large institutions',
                'price' => 7500,
                'billing_period' => '/month',
                'cta_text' => 'Request Demo',
                'featured' => true,
                'features' => [
                    ['name' => 'Up to 25,000 alumni', 'included' => true],
                    ['name' => 'Fully branded mobile app', 'included' => true],
                    ['name' => 'Advanced admin dashboard', 'included' => true],
                    ['name' => 'Comprehensive analytics', 'included' => true],
                    ['name' => 'Priority support', 'included' => true],
                    ['name' => 'Custom branding', 'included' => true],
                    ['name' => 'Advanced integrations', 'included' => true],
                    ['name' => 'Dedicated support', 'included' => true]
                ],
                'additional_info' => 'Includes implementation support'
            ],
            [
                'id' => 'custom_inst',
                'name' => 'Custom',
                'description' => 'For enterprise institutions',
                'price' => null,
                'billing_period' => 'Custom pricing',
                'cta_text' => 'Contact Sales',
                'featured' => false,
                'features' => [
                    ['name' => 'Unlimited alumni', 'included' => true],
                    ['name' => 'Multiple branded apps', 'included' => true],
                    ['name' => 'Custom admin features', 'included' => true],
                    ['name' => 'White-label solution', 'included' => true],
                    ['name' => 'Dedicated account manager', 'included' => true],
                    ['name' => 'Custom integrations', 'included' => true],
                    ['name' => 'SLA guarantees', 'included' => true],
                    ['name' => 'On-premise deployment', 'included' => true]
                ],
                'additional_info' => 'Contact us for custom quote'
            ]
        ];
    }

    /**
     * Get comparison features for audience
     */
    public function getComparisonFeatures(string $audience): array
    {
        $baseFeatures = [
            [
                'name' => 'Alumni Directory Access',
                'key' => 'directory_access',
                'description' => 'Search and connect with alumni'
            ],
            [
                'name' => 'Messaging',
                'key' => 'messaging',
                'description' => 'Direct messaging with other alumni'
            ],
            [
                'name' => 'Event Management',
                'key' => 'events',
                'description' => 'Create and manage networking events'
            ],
            [
                'name' => 'Job Board',
                'key' => 'job_board',
                'description' => 'Access to exclusive job opportunities'
            ],
            [
                'name' => 'Mentorship Matching',
                'key' => 'mentorship',
                'description' => 'AI-powered mentor matching'
            ],
            [
                'name' => 'Analytics',
                'key' => 'analytics',
                'description' => 'Insights and engagement metrics'
            ],
            [
                'name' => 'Support Level',
                'key' => 'support',
                'description' => 'Customer support availability'
            ]
        ];

        if ($audience === 'institutional') {
            $baseFeatures = array_merge($baseFeatures, [
                [
                    'name' => 'Branded Mobile App',
                    'key' => 'branded_app',
                    'description' => 'Custom mobile app with your branding'
                ],
                [
                    'name' => 'Admin Dashboard',
                    'key' => 'admin_dashboard',
                    'description' => 'Comprehensive administrative controls'
                ],
                [
                    'name' => 'Custom Integrations',
                    'key' => 'integrations',
                    'description' => 'Connect with your existing systems'
                ]
            ]);
        }

        return $baseFeatures;
    }

    /**
     * Get feature comparison matrix
     */
    public function getFeatureComparison(string $audience): array
    {
        $plans = $this->getPlansForAudience($audience);
        $features = $this->getComparisonFeatures($audience);

        return [
            'plans' => $plans,
            'features' => $features,
            'comparison_matrix' => $this->buildComparisonMatrix($plans, $features)
        ];
    }

    /**
     * Build comparison matrix
     */
    private function buildComparisonMatrix(array $plans, array $features): array
    {
        $matrix = [];

        foreach ($features as $feature) {
            $featureRow = [
                'feature' => $feature,
                'plan_values' => []
            ];

            foreach ($plans as $plan) {
                $featureRow['plan_values'][$plan['id']] = $this->getFeatureValueForPlan($feature['key'], $plan);
            }

            $matrix[] = $featureRow;
        }

        return $matrix;
    }

    /**
     * Get feature value for a specific plan
     */
    private function getFeatureValueForPlan(string $featureKey, array $plan): mixed
    {
        // Map feature keys to plan features
        $featureMap = [
            'directory_access' => function ($plan) {
                if ($plan['id'] === 'free') return 'Limited';
                return true;
            },
            'messaging' => function ($plan) {
                if ($plan['id'] === 'free') return '5/month';
                return 'Unlimited';
            },
            'events' => function ($plan) {
                if ($plan['id'] === 'free') return false;
                return true;
            },
            'job_board' => function ($plan) {
                return $this->planHasFeature($plan, 'Job board');
            },
            'mentorship' => function ($plan) {
                return $this->planHasFeature($plan, 'Mentorship');
            },
            'analytics' => function ($plan) {
                if ($this->planHasFeature($plan, 'Advanced analytics')) return 'Advanced';
                if ($this->planHasFeature($plan, 'analytics')) return 'Basic';
                return false;
            },
            'support' => function ($plan) {
                if ($this->planHasFeature($plan, 'Concierge')) return 'Concierge';
                if ($this->planHasFeature($plan, 'Priority')) return 'Priority';
                if ($this->planHasFeature($plan, 'support')) return 'Standard';
                return false;
            },
            'branded_app' => function ($plan) {
                return $this->planHasFeature($plan, 'branded app');
            },
            'admin_dashboard' => function ($plan) {
                return $this->planHasFeature($plan, 'dashboard');
            },
            'integrations' => function ($plan) {
                return $this->planHasFeature($plan, 'integrations');
            }
        ];

        if (isset($featureMap[$featureKey])) {
            return $featureMap[$featureKey]($plan);
        }

        return false;
    }

    /**
     * Check if plan has a specific feature
     */
    private function planHasFeature(array $plan, string $featureName): bool
    {
        foreach ($plan['features'] as $feature) {
            if (stripos($feature['name'], $featureName) !== false && $feature['included']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Track pricing interaction
     */
    public function trackPricingInteraction(array $data): void
    {
        try {
            // Log the interaction for analytics
            Log::info('Pricing interaction tracked', $data);

            // Here you could also send to analytics services like Google Analytics, Mixpanel, etc.
            // Example: $this->analyticsService->track($data);

        } catch (\Exception $e) {
            Log::error('Failed to track pricing interaction', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Get pricing statistics
     */
    public function getPricingStatistics(): array
    {
        return Cache::remember('pricing_statistics', 1800, function () {
            return [
                'total_plans' => count($this->getIndividualPlans()) + count($this->getInstitutionalPlans()),
                'individual_plans' => count($this->getIndividualPlans()),
                'institutional_plans' => count($this->getInstitutionalPlans()),
                'featured_plans' => $this->getFeaturedPlansCount(),
                'price_ranges' => [
                    'individual' => $this->getPriceRange('individual'),
                    'institutional' => $this->getPriceRange('institutional')
                ],
                'last_updated' => now()->toISOString()
            ];
        });
    }

    /**
     * Get count of featured plans
     */
    private function getFeaturedPlansCount(): array
    {
        $individualFeatured = collect($this->getIndividualPlans())->where('featured', true)->count();
        $institutionalFeatured = collect($this->getInstitutionalPlans())->where('featured', true)->count();

        return [
            'individual' => $individualFeatured,
            'institutional' => $institutionalFeatured,
            'total' => $individualFeatured + $institutionalFeatured
        ];
    }

    /**
     * Get price range for audience
     */
    private function getPriceRange(string $audience): array
    {
        $plans = $this->getPlansForAudience($audience);
        $prices = collect($plans)
            ->pluck('price')
            ->filter(fn($price) => $price !== null)
            ->values();

        if ($prices->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
            'average' => round($prices->average(), 2)
        ];
    }
}