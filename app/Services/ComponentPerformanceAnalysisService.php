<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComponentPerformanceAnalysisService
{
    public function __construct(
        private ComponentAnalyticsService $analyticsService
    ) {}

    /**
     * Analyze component performance for GrapeJS optimization
     */
    public function analyzeComponentPerformance(Component $component): array
    {
        $analysis = [
            'component_id' => $component->id,
            'analyzed_at' => now()->toISOString(),
            'performance_score' => 0,
            'metrics' => [],
            'recommendations' => [],
            'grapejs_optimizations' => [],
        ];

        // Analyze different performance aspects
        $analysis['metrics']['loading'] = $this->analyzeLoadingPerformance($component);
        $analysis['metrics']['rendering'] = $this->analyzeRenderingPerformance($component);
        $analysis['metrics']['interaction'] = $this->analyzeInteractionPerformance($component);
        $analysis['metrics']['accessibility'] = $this->analyzeAccessibilityPerformance($component);
        $analysis['metrics']['mobile'] = $this->analyzeMobilePerformance($component);

        // Calculate overall performance score
        $analysis['performance_score'] = $this->calculatePerformanceScore($analysis['metrics']);

        // Generate recommendations
        $analysis['recommendations'] = $this->generateRecommendations($analysis['metrics']);

        // Generate GrapeJS-specific optimizations
        $analysis['grapejs_optimizations'] = $this->generateGrapeJSOptimizations($component, $analysis['metrics']);

        return $analysis;
    }

    /**
     * Analyze loading performance
     */
    private function analyzeLoadingPerformance(Component $component): array
    {
        $config = $component->config ?? [];
        $metrics = [
            'score' => 100,
            'issues' => [],
            'optimizations' => [],
        ];

        // Check for heavy media content
        if ($this->hasHeavyMedia($config)) {
            $metrics['score'] -= 20;
            $metrics['issues'][] = 'Component contains heavy media that may slow loading';
            $metrics['optimizations'][] = 'Implement lazy loading for images and videos';
            $metrics['optimizations'][] = 'Use WebP format for images';
            $metrics['optimizations'][] = 'Add responsive image variants';
        }

        // Check for external dependencies
        if ($this->hasExternalDependencies($config)) {
            $metrics['score'] -= 15;
            $metrics['issues'][] = 'Component relies on external resources';
            $metrics['optimizations'][] = 'Bundle external resources locally';
            $metrics['optimizations'][] = 'Use CDN for external resources';
        }

        // Check for complex animations
        if ($this->hasComplexAnimations($config)) {
            $metrics['score'] -= 10;
            $metrics['issues'][] = 'Complex animations may impact loading performance';
            $metrics['optimizations'][] = 'Use CSS transforms instead of changing layout properties';
            $metrics['optimizations'][] = 'Implement will-change CSS property for animated elements';
        }

        return $metrics;
    }

    /**
     * Analyze rendering performance
     */
    private function analyzeRenderingPerformance(Component $component): array
    {
        $config = $component->config ?? [];
        $metrics = [
            'score' => 100,
            'issues' => [],
            'optimizations' => [],
        ];

        // Check DOM complexity
        $domComplexity = $this->calculateDOMComplexity($config);
        if ($domComplexity > 50) {
            $metrics['score'] -= 15;
            $metrics['issues'][] = 'High DOM complexity may impact rendering performance';
            $metrics['optimizations'][] = 'Simplify component structure';
            $metrics['optimizations'][] = 'Use CSS Grid or Flexbox for layout';
        }

        // Check for layout thrashing
        if ($this->hasLayoutThrashing($config)) {
            $metrics['score'] -= 20;
            $metrics['issues'][] = 'Component may cause layout thrashing';
            $metrics['optimizations'][] = 'Avoid changing layout properties during animations';
            $metrics['optimizations'][] = 'Use transform and opacity for animations';
        }

        // Check CSS efficiency
        $cssEfficiency = $this->analyzeCSSEfficiency($config);
        if ($cssEfficiency < 80) {
            $metrics['score'] -= 10;
            $metrics['issues'][] = 'Inefficient CSS may slow rendering';
            $metrics['optimizations'][] = 'Optimize CSS selectors';
            $metrics['optimizations'][] = 'Remove unused CSS rules';
        }

        return $metrics;
    }

    /**
     * Analyze interaction performance
     */
    private function analyzeInteractionPerformance(Component $component): array
    {
        $config = $component->config ?? [];
        $metrics = [
            'score' => 100,
            'issues' => [],
            'optimizations' => [],
        ];

        // Check for heavy event handlers
        if ($this->hasHeavyEventHandlers($config)) {
            $metrics['score'] -= 15;
            $metrics['issues'][] = 'Heavy event handlers may cause interaction delays';
            $metrics['optimizations'][] = 'Debounce or throttle event handlers';
            $metrics['optimizations'][] = 'Use passive event listeners where possible';
        }

        // Check touch target sizes
        if (!$this->hasOptimalTouchTargets($config)) {
            $metrics['score'] -= 10;
            $metrics['issues'][] = 'Touch targets may be too small for mobile devices';
            $metrics['optimizations'][] = 'Ensure touch targets are at least 44px';
            $metrics['optimizations'][] = 'Add adequate spacing between interactive elements';
        }

        // Check for smooth scrolling
        if ($this->hasScrollPerformanceIssues($config)) {
            $metrics['score'] -= 10;
            $metrics['issues'][] = 'Scroll performance may be impacted';
            $metrics['optimizations'][] = 'Use transform for scroll animations';
            $metrics['optimizations'][] = 'Implement virtual scrolling for large lists';
        }

        return $metrics;
    }

    /**
     * Analyze accessibility performance
     */
    private function analyzeAccessibilityPerformance(Component $component): array
    {
        $accessibility = $component->getAccessibilityMetadata();
        $metrics = [
            'score' => 100,
            'issues' => [],
            'optimizations' => [],
        ];

        // Check for ARIA labels
        if (empty($accessibility['ariaLabel']) && empty($accessibility['ariaLabelledBy'])) {
            $metrics['score'] -= 20;
            $metrics['issues'][] = 'Missing accessible name (aria-label or aria-labelledby)';
            $metrics['optimizations'][] = 'Add appropriate ARIA labels';
        }

        // Check semantic HTML
        if (($accessibility['semanticTag'] ?? 'div') === 'div') {
            $metrics['score'] -= 15;
            $metrics['issues'][] = 'Using generic div instead of semantic HTML';
            $metrics['optimizations'][] = 'Use semantic HTML elements (header, main, section, etc.)';
        }

        // Check keyboard navigation
        if (empty($accessibility['keyboardNavigation'])) {
            $metrics['score'] -= 15;
            $metrics['issues'][] = 'Keyboard navigation not properly configured';
            $metrics['optimizations'][] = 'Implement proper focus management';
            $metrics['optimizations'][] = 'Add keyboard event handlers';
        }

        // Check color contrast
        if (!$this->hasGoodColorContrast($component->config ?? [])) {
            $metrics['score'] -= 10;
            $metrics['issues'][] = 'Color contrast may not meet accessibility standards';
            $metrics['optimizations'][] = 'Ensure color contrast ratio meets WCAG guidelines';
        }

        return $metrics;
    }

    /**
     * Analyze mobile performance
     */
    private function analyzeMobilePerformance(Component $component): array
    {
        $responsiveConfig = $component->getResponsiveConfig();
        $metrics = [
            'score' => 100,
            'issues' => [],
            'optimizations' => [],
        ];

        // Check for mobile-specific configuration
        if (empty($responsiveConfig['mobile'])) {
            $metrics['score'] -= 25;
            $metrics['issues'][] = 'No mobile-specific configuration';
            $metrics['optimizations'][] = 'Add mobile-responsive configuration';
            $metrics['optimizations'][] = 'Optimize for touch interactions';
        }

        // Check viewport optimization
        if (!$this->isViewportOptimized($component->config ?? [])) {
            $metrics['score'] -= 15;
            $metrics['issues'][] = 'Component not optimized for mobile viewport';
            $metrics['optimizations'][] = 'Use relative units (rem, em, %) instead of fixed pixels';
            $metrics['optimizations'][] = 'Implement fluid typography';
        }

        // Check for mobile-friendly interactions
        if (!$this->hasMobileFriendlyInteractions($component->config ?? [])) {
            $metrics['score'] -= 10;
            $metrics['issues'][] = 'Interactions not optimized for mobile';
            $metrics['optimizations'][] = 'Implement touch gestures';
            $metrics['optimizations'][] = 'Optimize button and link sizes for touch';
        }

        return $metrics;
    }

    /**
     * Calculate overall performance score
     */
    private function calculatePerformanceScore(array $metrics): int
    {
        $totalScore = 0;
        $categoryCount = 0;

        foreach ($metrics as $category => $data) {
            if (isset($data['score'])) {
                $totalScore += $data['score'];
                $categoryCount++;
            }
        }

        return $categoryCount > 0 ? (int) round($totalScore / $categoryCount) : 0;
    }

    /**
     * Generate performance recommendations
     */
    private function generateRecommendations(array $metrics): array
    {
        $recommendations = [];

        foreach ($metrics as $category => $data) {
            if (isset($data['optimizations']) && !empty($data['optimizations'])) {
                $recommendations[$category] = [
                    'priority' => $this->calculatePriority($data['score']),
                    'optimizations' => $data['optimizations'],
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Generate GrapeJS-specific optimizations
     */
    private function generateGrapeJSOptimizations(Component $component, array $metrics): array
    {
        $optimizations = [
            'block_optimizations' => [],
            'style_optimizations' => [],
            'trait_optimizations' => [],
            'device_optimizations' => [],
        ];

        // Block optimizations
        if (isset($metrics['loading']['score']) && $metrics['loading']['score'] < 80) {
            $optimizations['block_optimizations'][] = [
                'type' => 'lazy_loading',
                'description' => 'Enable lazy loading for component blocks',
                'implementation' => 'Add data-lazy attribute to block definition',
            ];
        }

        // Style optimizations
        if (isset($metrics['rendering']['score']) && $metrics['rendering']['score'] < 80) {
            $optimizations['style_optimizations'][] = [
                'type' => 'css_optimization',
                'description' => 'Optimize CSS for better rendering performance',
                'implementation' => 'Use CSS containment and will-change properties',
            ];
        }

        // Trait optimizations
        if (isset($metrics['accessibility']['score']) && $metrics['accessibility']['score'] < 80) {
            $optimizations['trait_optimizations'][] = [
                'type' => 'accessibility_traits',
                'description' => 'Add accessibility-focused traits',
                'implementation' => 'Include ARIA label and role traits in trait manager',
            ];
        }

        // Device optimizations
        if (isset($metrics['mobile']['score']) && $metrics['mobile']['score'] < 80) {
            $optimizations['device_optimizations'][] = [
                'type' => 'responsive_optimization',
                'description' => 'Optimize for mobile devices',
                'implementation' => 'Add mobile-specific device manager configurations',
            ];
        }

        return $optimizations;
    }

    /**
     * Get performance trends over time
     */
    public function getPerformanceTrends(Component $component, int $days = 30): array
    {
        $cacheKey = "component_performance_trends_{$component->id}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($component, $days) {
            $trends = [];
            $startDate = now()->subDays($days);

            // Get performance data for each day
            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dayData = $this->getPerformanceDataForDate($component, $date);
                
                $trends[] = [
                    'date' => $date->toDateString(),
                    'performance_score' => $dayData['performance_score'] ?? 0,
                    'loading_time' => $dayData['loading_time'] ?? 0,
                    'interaction_delay' => $dayData['interaction_delay'] ?? 0,
                ];
            }

            return $trends;
        });
    }

    /**
     * Compare performance between component versions
     */
    public function compareVersionPerformance(ComponentVersion $version1, ComponentVersion $version2): array
    {
        $component1 = $version1->component;
        $component2 = $version2->component;

        // Temporarily set component config to version configs for analysis
        $originalConfig1 = $component1->config;
        $originalConfig2 = $component2->config;

        $component1->config = $version1->config;
        $component2->config = $version2->config;

        $analysis1 = $this->analyzeComponentPerformance($component1);
        $analysis2 = $this->analyzeComponentPerformance($component2);

        // Restore original configs
        $component1->config = $originalConfig1;
        $component2->config = $originalConfig2;

        return [
            'version_1' => [
                'version_number' => $version1->version_number,
                'performance_score' => $analysis1['performance_score'],
                'metrics' => $analysis1['metrics'],
            ],
            'version_2' => [
                'version_number' => $version2->version_number,
                'performance_score' => $analysis2['performance_score'],
                'metrics' => $analysis2['metrics'],
            ],
            'comparison' => [
                'score_difference' => $analysis2['performance_score'] - $analysis1['performance_score'],
                'improved_areas' => $this->findImprovedAreas($analysis1['metrics'], $analysis2['metrics']),
                'degraded_areas' => $this->findDegradedAreas($analysis1['metrics'], $analysis2['metrics']),
            ],
        ];
    }

    // Helper methods for performance analysis

    private function hasHeavyMedia(array $config): bool
    {
        return isset($config['background_media']) || 
               isset($config['video']) || 
               (isset($config['images']) && count($config['images']) > 5);
    }

    private function hasExternalDependencies(array $config): bool
    {
        $configString = json_encode($config);
        return str_contains($configString, 'http://') || str_contains($configString, 'https://');
    }

    private function hasComplexAnimations(array $config): bool
    {
        return isset($config['animations']) && 
               (count($config['animations']) > 3 || 
                isset($config['animations']['complex']) ||
                isset($config['animations']['duration']) && $config['animations']['duration'] > 1000);
    }

    private function calculateDOMComplexity(array $config): int
    {
        $complexity = 0;
        
        if (isset($config['components'])) {
            $complexity += count($config['components']) * 2;
        }
        
        if (isset($config['fields'])) {
            $complexity += count($config['fields']) * 1.5;
        }
        
        return (int) $complexity;
    }

    private function hasLayoutThrashing(array $config): bool
    {
        return isset($config['animations']) && 
               (isset($config['animations']['width']) || 
                isset($config['animations']['height']) ||
                isset($config['animations']['margin']) ||
                isset($config['animations']['padding']));
    }

    private function analyzeCSSEfficiency(array $config): int
    {
        // Simplified CSS efficiency analysis
        $efficiency = 100;
        
        if (isset($config['styles']) && is_array($config['styles'])) {
            $styleCount = count($config['styles']);
            if ($styleCount > 20) {
                $efficiency -= 20;
            }
        }
        
        return $efficiency;
    }

    private function hasHeavyEventHandlers(array $config): bool
    {
        return isset($config['events']) && count($config['events']) > 5;
    }

    private function hasOptimalTouchTargets(array $config): bool
    {
        return isset($config['touch_targets']) && 
               ($config['touch_targets']['min_size'] ?? 0) >= 44;
    }

    private function hasScrollPerformanceIssues(array $config): bool
    {
        return isset($config['scroll_animations']) || 
               (isset($config['list_items']) && ($config['list_items'] ?? 0) > 100);
    }

    private function hasGoodColorContrast(array $config): bool
    {
        // Simplified color contrast check
        return isset($config['accessibility']['color_contrast']) && 
               $config['accessibility']['color_contrast'] >= 4.5;
    }

    private function isViewportOptimized(array $config): bool
    {
        return isset($config['responsive']) && 
               !empty($config['responsive']['mobile']);
    }

    private function hasMobileFriendlyInteractions(array $config): bool
    {
        return isset($config['mobile_interactions']) || 
               (isset($config['touch_enabled']) && $config['touch_enabled']);
    }

    private function calculatePriority(int $score): string
    {
        return match (true) {
            $score < 60 => 'high',
            $score < 80 => 'medium',
            default => 'low',
        };
    }

    private function getPerformanceDataForDate(Component $component, Carbon $date): array
    {
        // This would typically fetch real performance data from analytics
        // For now, return mock data
        return [
            'performance_score' => rand(70, 95),
            'loading_time' => rand(100, 500),
            'interaction_delay' => rand(10, 50),
        ];
    }

    private function findImprovedAreas(array $metrics1, array $metrics2): array
    {
        $improved = [];
        
        foreach ($metrics2 as $category => $data2) {
            if (isset($metrics1[$category]) && 
                isset($data2['score']) && 
                isset($metrics1[$category]['score'])) {
                
                if ($data2['score'] > $metrics1[$category]['score']) {
                    $improved[] = [
                        'category' => $category,
                        'improvement' => $data2['score'] - $metrics1[$category]['score'],
                    ];
                }
            }
        }
        
        return $improved;
    }

    private function findDegradedAreas(array $metrics1, array $metrics2): array
    {
        $degraded = [];
        
        foreach ($metrics2 as $category => $data2) {
            if (isset($metrics1[$category]) && 
                isset($data2['score']) && 
                isset($metrics1[$category]['score'])) {
                
                if ($data2['score'] < $metrics1[$category]['score']) {
                    $degraded[] = [
                        'category' => $category,
                        'degradation' => $metrics1[$category]['score'] - $data2['score'],
                    ];
                }
            }
        }
        
        return $degraded;
    }
}