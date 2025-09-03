<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentTheme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ComponentAccessibilityService
{
    /**
     * WCAG 2.1 AA compliance thresholds
     */
    protected const WCAG_AA_CONTRAST_RATIO = 4.5;
    protected const WCAG_AAA_CONTRAST_RATIO = 7.0;
    protected const WCAG_AA_LARGE_TEXT_CONTRAST_RATIO = 3.0;
    protected const WCAG_AAA_LARGE_TEXT_CONTRAST_RATIO = 4.5;

    /**
     * Assess component accessibility compliance
     */
    public function assessComponent(Component $component): array
    {
        $cacheKey = "accessibility_assessment_{$component->id}";
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $assessment = [
            'component_id' => $component->id,
            'component_name' => $component->name,
            'category' => $component->category,
            'overall_score' => 0,
            'grade' => 'D',
            'is_compliant' => false,
            'compliance_level' => 'A',
            'timestamp' => now()->toISOString(),
            'issues' => [],
            'successes' => [],
            'suggestions' => [],
            'breakdown' => []
        ];

        // Analyze different accessibility aspects
        $aspectResults = [
            'semantic_html' => $this->analyzeSemanticHTML($component),
            'color_contrast' => $this->analyzeColorContrast($component),
            'keyboard_navigation' => $this->analyzeKeyboardNavigation($component),
            'screen_reader_support' => $this->analyzeScreenReaderSupport($component),
            'mobile_accessibility' => $this->analyzeMobileAccessibility($component)
        ];

        // Combine all aspect results
        foreach ($aspectResults as $aspect => $result) {
            $assessment['issues'] = array_merge($assessment['issues'], $result['issues']);
            $assessment['successes'] = array_merge($assessment['successes'], $result['successes']);
            $assessment['suggestions'] = array_merge($assessment['suggestions'], $result['suggestions']);
            $assessment['breakdown'][$aspect] = $result;
            $assessment['score_' . $aspect] = $result['score'];
        }

        // Calculate overall score and grade
        $assessment['overall_score'] = $this->calculateOverallScore($assessment);
        $assessment['grade'] = $this->calculateGrade($assessment['overall_score']);
        $assessment['is_compliant'] = $this->determineCompliance($assessment);

        Cache::put($cacheKey, $assessment, now()->addHours(24));

        return $assessment;
    }

    /**
     * Analyze semantic HTML structure
     */
    protected function analyzeSemanticHTML(Component $component): array
    {
        $config = $component->config ?? [];
        $issues = [];
        $successes = [];
        $suggestions = [];
        $score = 100;

        // Check for proper semantic elements
        if (!isset($config['semantic_tag']) || $config['semantic_tag'] === 'div') {
            $issues[] = [
                'rule_id' => '1.3.1',
                'description' => 'Non-semantic HTML elements used (div instead of semantic element)',
                'severity' => 'medium',
                'impact' => 'Screen readers cannot identify content structure properly'
            ];
            $score -= 30;
            $suggestions[] = 'Use semantic HTML elements like header, nav, main, section, article, aside, footer';
        } else {
            $successes[] = 'Uses semantic HTML elements';
        }

        // Check for proper heading hierarchy
        $headingLevel = $this->detectHeadingLevel($config);
        if ($headingLevel > 0) {
            $successes[] = "Proper heading level ({$config['semantic_tag']}) detected";
        } else {
            $issues[] = [
                'rule_id' => '2.4.6',
                'description' => 'Missing or improper heading structure',
                'severity' => 'medium',
                'impact' => 'Users cannot navigate content efficiently'
            ];
            $score -= 20;
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'successes' => $successes,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Analyze color contrast ratios
     */
    protected function analyzeColorContrast(Component $component): array
    {
        $issues = [];
        $successes = [];
        $suggestions = [];
        $score = 100;

        $config = $component->config ?? [];
        $theme = $component->theme;

        // Get colors from component config or theme
        $colors = $this->getColorsForAnalysis($component);

        if (!isset($colors['text']) || !isset($colors['background'])) {
            $issues[] = [
                'rule_id' => '1.4.3',
                'description' => 'Text and background colors not defined',
                'severity' => 'high',
                'impact' => 'Text may not be readable for users with visual impairments'
            ];
            $score -= 100;
            return [
                'score' => max(0, $score),
                'issues' => $issues,
                'successes' => $successes,
                'suggestions' => $suggestions
            ];
        }

        $contrastRatio = $this->calculateContrastRatio($colors['text'], $colors['background']);

        if ($contrastRatio >= self::WCAG_AA_CONTRAST_RATIO) {
            $successes[] = "Excellent color contrast ratio: {$contrastRatio}:1 (meets WCAG AA standards)";
        } elseif ($contrastRatio >= self::WCAG_AA_LARGE_TEXT_CONTRAST_RATIO) {
            $successes[] = "Acceptable color contrast ratio: {$contrastRatio}:1 (meets WCAG AA for large text)";
        } else {
            $issues[] = [
                'rule_id' => '1.4.3',
                'description' => "Poor color contrast ratio: {$contrastRatio}:1 (below WCAG AA standard of 4.5:1)",
                'severity' => 'high',
                'impact' => 'Text may be difficult or impossible to read for users with visual impairments'
            ];
            $score -= 80;
            $suggestions[] = 'Adjust colors to meet WCAG AA contrast ratio of 4.5:1';
            $suggestions[] = 'Use tools like WebAIM Contrast Checker to verify ratios';
            $suggestions[] = 'Consider using darker text on lighter backgrounds or vice versa';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'successes' => $successes,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Analyze keyboard navigation support
     */
    protected function analyzeKeyboardNavigation(Component $component): array
    {
        $config = $component->config ?? [];
        $accessibility = $config['accessibility'] ?? [];
        $issues = [];
        $successes = [];
        $suggestions = [];
        $score = 100;

        // Check for focus management
        if (!isset($accessibility['focusable']) && $this->componentMayNeedFocus($component->category)) {
            $issues[] = [
                'rule_id' => '2.1.1',
                'description' => 'Component may require keyboard focus management',
                'severity' => 'medium',
                'impact' => 'Users cannot access component using keyboard-only navigation'
            ];
            $score -= 30;
            $suggestions[] = 'Ensure all interactive elements are keyboard accessible';
            $suggestions[] = 'Implement proper focus management for complex components';
        } else {
            $successes[] = 'Keyboard navigation considerations detected';
        }

        // Check for logical tab order
        if ($this->hasMultipleInteractiveElements($component)) {
            $tabOrder = $accessibility['tab_order'] ?? null;
            if (!$tabOrder) {
                $issues[] = [
                    'rule_id' => '2.4.3',
                    'description' => 'Complex component may have illogical tab order',
                    'severity' => 'medium',
                    'impact' => 'Users may experience confusing navigation flow'
                ];
                $score -= 20;
                $suggestions[] = 'Ensure logical tab order for all interactive elements';
            } else {
                $successes[] = 'Logical tab order defined';
            }
        }

        // Check for focus indicators
        $focusIndicators = $accessibility['focus_indicators'] ?? [];
        if (empty($focusIndicators)) {
            $issues[] = [
                'rule_id' => '2.4.7',
                'description' => 'Missing visible focus indicators',
                'severity' => 'medium',
                'impact' => 'Users cannot see where they are when navigating with keyboard'
            ];
            $score -= 30;
            $suggestions[] = 'Add visible focus indicators (outline, background, border changes)';
            $suggestions[] = 'Ensure focus indicators have adequate contrast';
        } else {
            $successes[] = 'Focus indicators configured';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'successes' => $successes,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Analyze screen reader support
     */
    protected function analyzeScreenReaderSupport(Component $component): array
    {
        $config = $component->config ?? [];
        $accessibility = $config['accessibility'] ?? [];
        $issues = [];
        $successes = [];
        $suggestions = [];
        $score = 100;

        // Check for ARIA labels
        if (!isset($accessibility['aria_label'])) {
            $issues[] = [
                'rule_id' => '4.1.2',
                'description' => 'Missing ARIA label for component',
                'severity' => 'medium',
                'impact' => 'Screen readers cannot announce component purpose'
            ];
            $score -= 30;
            $suggestions[] = 'Add descriptive aria-label attribute';
            $suggestions[] = 'Consider aria-labelledby if pointing to existing text';
        } else {
            $successes[] = 'ARIA label configured for screen readers';
        }

        // Check for ARIA roles
        if (!isset($accessibility['role']) && $this->componentNeedsExplicitRole($component->category)) {
            $issues[] = [
                'rule_id' => '4.1.2',
                'description' => 'Missing ARIA role for complex component',
                'severity' => 'medium',
                'impact' => 'Screen readers cannot understand component type and purpose'
            ];
            $score -= 25;
            $suggestions[] = 'Add appropriate ARIA role attribute';
        } else {
            $successes[] = 'Appropriate ARIA role defined';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'successes' => $successes,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Analyze mobile accessibility
     */
    protected function analyzeMobileAccessibility(Component $component): array
    {
        $config = $component->config ?? [];
        $responsive = $config['responsive'] ?? [];
        $issues = [];
        $successes = [];
        $suggestions = [];
        $score = 100;

        // Check for mobile-specific configuration
        if (isset($responsive['mobile'])) {
            $mobileConfig = $responsive['mobile'];

            // Check font size scaling
            $fontSize = $mobileConfig['font_size'] ?? null;
            if ($fontSize && $this->isSmallFontSize($fontSize)) {
                $issues[] = [
                    'rule_id' => '1.4.4',
                    'description' => 'Mobile font size may be too small (below recommended 14px)',
                    'severity' => 'medium',
                    'impact' => 'Text may be difficult to read on mobile devices'
                ];
                $score -= 30;
                $suggestions[] = 'Ensure minimum font size of 14px (16px preferred) on mobile';
                $suggestions[] = 'Test readability on actual mobile devices';
            } else {
                $successes[] = 'Mobile font size configured appropriately';
            }
        } else {
            $issues[] = [
                'rule_id' => '1.4.10',
                'description' => 'Missing mobile-responsive considerations',
                'severity' => 'low',
                'impact' => 'Component may not work well on mobile devices'
            ];
            $score -= 20;
            $suggestions[] = 'Add mobile-specific configuration and test on mobile devices';
        }

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'successes' => $successes,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Calculate overall accessibility score
     */
    protected function calculateOverallScore(array $assessment): float
    {
        $scores = [
            $assessment['score_semantic_html'] ?? 100,
            $assessment['score_color_contrast'] ?? 100,
            $assessment['score_keyboard_navigation'] ?? 100,
            $assessment['score_screen_reader_support'] ?? 100,
            $assessment['score_mobile_accessibility'] ?? 100
        ];

        // Weighted average (some aspects are more critical than others)
        $weights = [0.20, 0.25, 0.20, 0.20, 0.15];
        $weightedScore = 0;
        $totalWeight = 0;

        foreach ($scores as $index => $score) {
            $weightedScore += $score * $weights[$index];
            $totalWeight += $weights[$index];
        }

        return $totalWeight > 0 ? round($weightedScore / $totalWeight, 1) : 0;
    }

    /**
     * Calculate grade based on score
     */
    protected function calculateGrade(float $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'A-';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 65) return 'C+';
        if ($score >= 60) return 'C';
        if ($score >= 55) return 'C-';
        if ($score >= 50) return 'D+';
        if ($score >= 40) return 'D';
        return 'F';
    }

    /**
     * Determine if component meets basic accessibility compliance
     */
    protected function determineCompliance(array $assessment): bool
    {
        $overallScore = $assessment['overall_score'];
        $criticalIssues = array_filter($assessment['issues'], fn($issue) => $issue['severity'] === 'high');

        // Must score at least 70% with no critical issues to be compliant
        return $overallScore >= 70 && count($criticalIssues) === 0;
    }

    /**
     * Calculate color contrast ratio
     */
    protected function calculateContrastRatio(string $color1, string $color2): float
    {
        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);

        if (!$rgb1 || !$rgb2) {
            return 0.0;
        }

        $l1 = $this->getRelativeLuminance($rgb1);
        $l2 = $this->getRelativeLuminance($rgb2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return $lighter > 0 && $darker >= 0 ? ($lighter + 0.05) / ($darker + 0.05) : 0.0;
    }

    /**
     * Convert hex color to RGB
     */
    protected function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Calculate relative luminance
     */
    protected function getRelativeLuminance(array $rgb): float
    {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Get colors for analysis from component or theme
     */
    protected function getColorsForAnalysis(Component $component): array
    {
        $config = $component->config ?? [];

        // Try to get colors from theme first, then component
        if ($component->theme && isset($component->theme->config['colors'])) {
            return $component->theme->config['colors'];
        }

        return $config['theme_colors'] ?? [];
    }

    /**
     * Check if component needs keyboard focus management
     */
    protected function componentMayNeedFocus(string $category): bool
    {
        $focusableCategories = ['forms', 'ctas', 'testimonials', 'statistics', 'hero'];
        return in_array($category, $focusableCategories);
    }

    /**
     * Check if component has multiple interactive elements
     */
    protected function hasMultipleInteractiveElements(Component $component): bool
    {
        $config = $component->config ?? [];

        switch ($component->category) {
            case 'forms':
                return count($config['fields'] ?? []) > 1;
            case 'ctas':
                return count($config['buttons'] ?? []) > 1;
            default:
                return false;
        }
    }

    /**
     * Detect heading level from semantic tag
     */
    protected function detectHeadingLevel(array $config): int
    {
        $tag = strtolower($config['semantic_tag'] ?? '');

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Check if component needs explicit ARIA role
     */
    protected function componentNeedsExplicitRole(string $category): bool
    {
        $complexCategories = ['forms', 'testimonials', 'statistics', 'hero'];
        return in_array($category, $complexCategories);
    }

    /**
     * Check if font size is too small for mobile
     */
    protected function isSmallFontSize(string $fontSize): bool
    {
        // Extract numeric value from font size
        if (preg_match('/(\d+(\.\d+)?)px/', $fontSize, $matches)) {
            return (float) $matches[1] < 14; // Below recommended 14px
        }

        return false;
    }

    /**
     * Get accessibility assessment summary for multiple components
     */
    public function getAccessibilitySummary(array $componentIds, ?int $tenantId = null): array
    {
        $query = Component::whereIn('id', $componentIds);
        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        $components = $query->get();
        $summary = [
            'total_components' => $components->count(),
            'grades_count' => [
                'A+' => 0, 'A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0,
                'B-' => 0, 'C+' => 0, 'C' => 0, 'C-' => 0, 'D+' => 0,
                'D' => 0, 'F' => 0
            ],
            'compliance_levels' => [
                'compliant' => 0,
                'partial' => 0,
                'non_compliant' => 0
            ],
            'critical_issues_by_type' => [
                'color_contrast' => 0,
                'keyboard_navigation' => 0,
                'screen_reader_support' => 0,
                'semantic_html' => 0
            ],
            'average_score' => 0,
            'component_assessments' => []
        ];

        $totalScore = 0;

        foreach ($components as $component) {
            $assessment = $this->assessComponent($component);
            $summary['component_assessments'][] = [
                'id' => $component->id,
                'name' => $component->name,
                'score' => $assessment['overall_score'],
                'grade' => $assessment['grade'],
                'is_compliant' => $assessment['is_compliant']
            ];

            $summary['grades_count'][$assessment['grade']]++;
            $totalScore += $assessment['overall_score'];

            // Categorize compliance
            if ($assessment['is_compliant']) {
                $summary['compliance_levels']['compliant']++;
            } elseif ($assessment['overall_score'] >= 50) {
                $summary['compliance_levels']['partial']++;
            } else {
                $summary['compliance_levels']['non_compliant']++;
            }

            // Count critical issues
            foreach ($assessment['issues'] as $issue) {
                if (($issue['severity'] ?? 'low') === 'high') {
                    $ruleId = $issue['rule_id'] ?? '';
                    if (str_starts_with($ruleId, '1.4.3')) {
                        $summary['critical_issues_by_type']['color_contrast']++;
                    } elseif (str_starts_with($ruleId, '2.1') || str_starts_with($ruleId, '2.4.7')) {
                        $summary['critical_issues_by_type']['keyboard_navigation']++;
                    } elseif (str_starts_with($ruleId, '4.1.2')) {
                        $summary['critical_issues_by_type']['screen_reader_support']++;
                    } elseif (str_starts_with($ruleId, '1.3.1')) {
                        $summary['critical_issues_by_type']['semantic_html']++;
                    }
                }
            }
        }

        $summary['average_score'] = $components->count() > 0
            ? round($totalScore / $components->count(), 1)
            : 0;

        return $summary;
    }

    /**
     * Get accessibility recommendations for a component
     */
    public function getRecommendations(Component $component): array
    {
        $assessment = $this->assessComponent($component);

        return [
            'current_score' => $assessment['overall_score'],
            'current_grade' => $assessment['grade'],
            'is_compliant' => $assessment['is_compliant'],
            'issues_count' => count($assessment['issues']),
            'successes_count' => count($assessment['successes']),
            'immediate_actions' => $this->getImmediateActions($assessment),
            'high_impact_improvements' => $this->getHighImpactImprovements($assessment),
            'ongoing_maintenance' => $this->getOngoingMaintenance($assessment),
            'testing_checklist' => $this->getTestingChecklist(),
            'estimated_improvement' => $this->estimateScoreImprovement($assessment)
        ];
    }

    /**
     * Get immediate actions based on assessment
     */
    protected function getImmediateActions(array $assessment): array
    {
        $actions = [];

        foreach ($assessment['issues'] as $issue) {
            if (isset($issue['severity']) && $issue['severity'] === 'high') {
                $actions[] = [
                    'priority' => 'critical',
                    'description' => $issue['description'],
                    'suggestion' => $this->getSuggestionForIssue($issue),
                    'effort' => 'medium'
                ];
            }
        }

        return array_slice($actions, 0, 3); // Top 3 critical issues
    }

    /**
     * Get high impact improvements
     */
    protected function getHighImpactImprovements(array $assessment): array
    {
        $improvements = [];

        foreach ($assessment['suggestions'] as $suggestion) {
            $improvements[] = [
                'description' => $suggestion,
                'impact' => 'high',
                'effort' => 'medium'
            ];
        }

        return $improvements;
    }

    /**
     * Get ongoing maintenance recommendations
     */
    protected function getOngoingMaintenance(array $assessment): array
    {
        return [
            [
                'description' => 'Regular accessibility audits during development',
                'impact' => 'high',
                'effort' => 'low'
            ],
            [
                'description' => 'Test with keyboard-only navigation',
                'impact' => 'medium',
                'effort' => 'low'
            ],
            [
                'description' => 'Validate color contrast ratios',
                'impact' => 'high',
                'effort' => 'low'
            ]
        ];
    }

    /**
     * Get testing checklist
     */
    protected function getTestingChecklist(): array
    {
        return [
            'Navigate component using only keyboard (Tab, Enter, Space, Arrow keys)',
            'Test with screen reader software (NVDA, JAWS, VoiceOver)',
            'Validate color contrast ratios with automated tools',
            'Ensure touch targets are at least 44px on mobile devices',
            'Test that text can resize up to 200% without breaking layout',
            'Verify form validation messages are announced to screen readers',
            'Check heading hierarchy for logical document structure',
            'Test focus indicators are visible and have adequate contrast'
        ];
    }

    /**
     * Get suggestion for a specific issue
     */
    protected function getSuggestionForIssue(array $issue): string
    {
        $ruleId = $issue['rule_id'] ?? '';

        switch ($ruleId) {
            case '1.4.3':
                return 'Adjust text/background color combinations to meet WCAG AA contrast ratio of 4.5:1';
            case '2.1.1':
                return 'Ensure all interactive elements are keyboard accessible';
            case '1.3.1':
                return 'Replace div elements with appropriate semantic HTML elements';
            case '2.4.7':
                return 'Add visible focus indicators with adequate contrast';
            case '4.1.2':
                return 'Add descriptive ARIA labels and roles where needed';
            default:
                return 'Review accessibility guidelines for this component type';
        }
    }

    /**
     * Estimate potential score improvement
     */
    protected function estimateScoreImprovement(array $assessment): float
    {
        $currentScore = $assessment['overall_score'];
        $issuesCount = count($assessment['issues']);

        // Rough estimation: each fixed issue improves score by 5-10 points
        $potentialImprovement = min($issuesCount * 7.5, 100 - $currentScore);

        return round($potentialImprovement, 1);
    }

    /**
     * Bulk assess accessibility for multiple components
     */
    public function batchAssess(Collection $components): array
    {
        $assessments = [];
        $components->each(function ($component) use (&$assessments) {
            $assessments[$component->id] = $this->assessComponent($component);
        });

        return $assessments;
    }

    /**
     * Clear accessibility cache for a component
     */
    public function clearCache(?Component $component = null): void
    {
        if ($component) {
            Cache::forget("accessibility_assessment_{$component->id}");
        } else {
            // Clear all accessibility-related cache (would need more specific implementation)
            Cache::flush();
        }
    }
}